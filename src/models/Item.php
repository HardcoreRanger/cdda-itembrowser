<?php

class Item implements Robbo\Presenter\PresentableInterface
{
    use MagicModel;

    protected $data;
    protected $repo;

    private $cut_pairs = array(
      "cotton" => "rag",
      "leather" => "leather",
      "fur" => "fur",
      "nomex" => "nomex",
      "plastic" => "plastic_chunk",
      "kevlar" => "kevlar_plate",
      "wood" => "skewer",
    );

    public function __construct(Repositories\RepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function load($data)
    {
        if (!isset($data->material) || (is_array($data->material) && count($data->material) === 0)) {
            $data->material = array("null", "null");
        }
        if (!is_array($data->material)) {
            $data->material = array($data->material, "null");
        }
        if (!isset($data->material[1])) {
            $data->material[1] = "null";
        }

        if (!isset($data->flags)) {
            $data->flags = array();
        } else {
            if (isset($data->flags[0])) {
                $data->flags = array_flip((array) $data->flags);
            }
        }
        if (!isset($data->qualities)) {
            $data->qualities = array();
        }

        $this->data = $data;
    }

    public function loadDefault($id)
    {
        $data = json_decode('{"id":"'.$id.'","name":"'.$id.'?","type":"invalid"}');
        $this->load($data);
    }

    public function getColor()
    {
        if (!isset($this->data->color)) {
            return "white";
        }
        $color = str_replace("_", "", $this->data->color);
        $colorTable = array(
            "lightred" => "indianred",
        );
        if (isset($colorTable[$color])) {
            return $colorTable[$color];
        }

        return $color;
    }

    public function getSymbol()
    {
        if (!isset($this->data->symbol)) {
            return " ";
        }

        return $this->data->symbol;
    }

    public function getRawName()
    {
        if (!isset($this->data->name)) {
            return;
        }

        $name = $this->data->name;
        if (is_object($this->data->name)) {
            if (isset($this->data->name->str)) {
                $name = $this->data->name->str;
            } elseif (isset($this->data->name->str_sp)) {
                $name = $this->data->name->str_sp;
            } else {
                $name = '';
            }
        } else if (is_array($name)) {
            $name = $name[0];
        }

        return ($this->type == "bionic" ? "CBM: " : "").$name; //." (".$this->data->id.")";
    }

    public function getRecipes()
    {
        if (isset($this->data->original_id)) {
            return $this->repo->allModels("Recipe", "item.recipes.{$this->data->original_id}");
        }

        return $this->repo->allModels("Recipe", "item.recipes.{$this->data->id}");
    }

    public function getDisassembly()
    {
        return $this->repo->allModels("Recipe", "item.disassembly.{$this->id}");
    }

    public function getDisassembledFrom()
    {
        return $this->repo->allModels("Recipe", "item.disassembledFrom.$this->id");
    }

    public function getDeconstructFrom()
    {
        return $this->repo->allModels("Furniture", "item.deconstructFrom.$this->id");
    }

    public function getBashFromTerrain()
    {
        return $this->repo->allModels("Terrain", "item.bashFromTerrain.$this->id");
    }

    public function getToolFor()
    {
        return $this->repo->allModels("Item", "item.toolFor.$this->id");
    }

    public function getHasVpartlist()
    {
        return count($this->repo->allModels("Item", "vpartlist.$this->id"));
    }

    public function getVpartFor()
    {
        $vparts = $this->repo->allModels("Item", "vpartlist.$this->id");
        $string1 = "";
        $inner = array();
        foreach ($vparts as $item) {
            // build link name with name and ID to distinguish between multiple usage of vehicle part names
            $inner[] = '<a href="'.route("item.view", array("id" => $item->id)).'">'.$item->name.(substr($item->id, 6) !== $item->name ? " (".substr($item->id, 6).")" : "").'</a>';
        }

        return "&gt; ".implode("<br>&gt; ", $inner)."\n";
    }

    public function count($type)
    {
        return $this->repo->raw("item.count.$this->id.$type", 0);
    }

    public function flatcount($type)
    {
        return $this->repo->raw("item.count.$this->original_id.$type", 0);
    }

    public function getToolCategories()
    {
        $categories = $this->repo->raw("item.categories.{$this->id}");
        if (empty($categories)) {
            return array("CC_NONE" => "CC_NONE");
        }

        return $categories;
    }

    public function getToolForCategory($category)
    {
        return $this->repo->allModels("Recipe", "item.toolForCategory.{$this->data->id}.$category");
    }

    public function getLearn()
    {
        return $this->repo->allModels("Recipe", "item.learn.{$this->data->id}");
    }

    public function getIsArmor()
    {
        return in_array($this->data->type, ["ARMOR", "TOOL_ARMOR"]);
    }

    public function getIsConsumable()
    {
        return $this->data->type == "COMESTIBLE";
    }

    public function getIsAmmo()
    {
        return $this->data->type == "AMMO";
    }

    public function getIsVehiclePart()
    {
        return strtoupper($this->data->type) == "VEHICLE_PART";
    }

    public function getIsBook()
    {
        return $this->data->type == "BOOK";
    }

    public function getIsGun()
    {
        return $this->data->type == "GUN";
    }

    public function getIsBionic()
    {
        return $this->data->type == "bionic";
    }

    public function getIsBionicItem()
    {
        return $this->data->type == "BIONIC_ITEM";
    }

    public function getDifficulty()
    {
        return isset($this->data->difficulty) ? $this->data->difficulty : 0;
    }

    public function protection($type)
    {
        $mat1 = $this->material1;
        $mat2 = $this->material2;

        $variable = "{$type}_resist";
        $thickness = $this->material_thickness;
        if ($thickness < 1 || ($variable == "acid_resist" || $variable == "fire_resist")) {
            $thickness = 1;
        }

        $val = 0;
        if ($mat2 == "null" || $mat2->id == "null") {
            $val = $thickness * $mat1->$variable;
        } else {
            $val = $thickness * (($mat1->$variable + $mat2->$variable) / 2);
        }

        if (($variable == "acid_resist" || $variable == "fire_resist") && $this->environmental_protection < 10) {
            $val = $this->environmental_protection / 10.0 * $val;
        }

        return round($val);
    }

    public function getIsTool()
    {
        return isset($this->data->max_charges) and isset($this->data->ammo);
    }

    public function getStackSize()
    {
        return isset($this->data->stack_size) ? $this->data->stack_size : 1;
    }

    public function getVolume()
    {
        if (!isset($this->data->volume)) {
            return;
        }
//         if ($this->isAmmo) {
//             return round($this->data->volume/$this->stackSize);
//         }

        return $this->data->volume;
    }

    public function getWeight()
    {
        if (!isset($this->data->weight)) {
            return;
        }
        if ($this->isAmmo) {
            return floatval($this->data->weight) * $this->data->count;
        }

        return floatval($this->data->weight);
    }

    public function getMovesPerAttack()
    {
        if (!isset($this->data->weight) || !isset($this->data->volume)) {
            return;
        }

        return floor(65 + 4 * floatval($this->volume) + floatval($this->weight) / 60);
    }

    public function getToHit()
    {
        if (!isset($this->data->cib_to_hit)) {
            return 0;
        }

        return sprintf("%+d", $this->data->cib_to_hit);
    }

    public function getPierce()
    {
        if (isset($this->data->damage->armor_penetration)) {
            return $this->data->damage->armor_penetration;
        } else if (isset($this->data->pierce)) {
            return $this->data->pierce;
        }
        return 0;
    }

    public function getMaterial1()
    {
        return $this->repo->getModel("Material", $this->data->material[0]);
    }

    public function getMaterial2()
    {
        return $this->repo->getModel("Material", $this->data->material[1]);
    }

    public function getCanBeCut()
    {
        if (!$this->volume) {
            return false;
        }
        $material1 = $this->material1->id;
        $material2 = $this->material2->id;

        return in_array($material1, array_keys($this->cut_pairs)) and
              in_array($material2, array_keys($this->cut_pairs));
    }

    public function getCutResult()
    {
        $results = [];
        $materials = $this->materials;

        foreach ($materials as $material) {
            $results[] = [
                "amount" => $this->volume / count($materials),
                "item" => $this->repo->getModel("Item", $this->cut_pairs[$material->id]),
            ];
        }

        return $results;
    }

    public function getIsResultOfCutting()
    {
        return in_array($this->id, array_keys(array_flip($this->cut_pairs)));
    }

    public function getMaterialToCut()
    {
        $pairs = array_flip($this->cut_pairs);

        return $pairs[$this->id];
    }

    public function getAmmoTypes()
    {
        $ammolist = $this->data->ammo;
        $ammotypes = array();
        if (is_array($ammolist)) {
            foreach ($ammolist as $ammoitem) {
                $nextammolist = $this->repo->allModels("Item", "ammo.$ammoitem");
                if (is_array($nextammolist)) {
                    $ammotypes = array_merge($ammotypes, $nextammolist);
                }
            }
        } else {
            $ammotypes = $this->repo->allModels("Item", "ammo.$ammolist");
        }

        // ?????????????????? mod ???????????????????????????
        // ????????????????????? id ?????????????????????????????????
        $tmp = [];
        foreach ($ammotypes as &$ammotype) {
            $tmp = array_merge($tmp, $this->repo->getMultiModelOrFail("Item", $ammotype->id));
        }
        $ammotypes = [];
        // ??????????????????????????????????????????????????????????????????????????????
        foreach ($tmp as &$ele) {
            $hash = $ele->rawname.$ele->modname;
            $ammotypes[$hash] = $ele;
        }
        $ammotypes = array_values($ammotypes);

        foreach ($ammotypes as &$ammotype) {
            $ammo_damage_multiplier = 1.0;
            if ($this->data->type == "GUN") {
                if ($ammotype->prop_damage > 0) {
                    $ammo_damage_multiplier = $ammotype->prop_damage;
                } else if (isset($ammotype->data->damage->constant_damage_multiplier)) {
                    $ammo_damage_multiplier = $ammotype->data->damage->constant_damage_multiplier;
                }
            }

            $result = floatval($ammotype->damage);
            if (is_object($result)) {
                $result = $result->amount;
            }
            $rdamage = 0;
            if (isset($this->data->ranged_damage)) {
                $rdamage = $this->data->ranged_damage;
            }
            if (is_object($rdamage)) {
                $rdamage = $rdamage->amount;
            }
            if (strchr($rdamage, '+') !== FALSE) {
                $rdamage = array_sum(explode("+", $rdamage));
            }
            if ($this->data->type == "GUN") {
                $result = ($result + $rdamage) * $ammo_damage_multiplier;
            }
            $ammotype->damage = $result;
        }
        unset($ammotype);

        return $ammotypes;
    }

    public function isMadeOf($material)
    {
        return stristr($this->material1->name, $material);
    }

    public function getPresenter()
    {
        return new Presenters\Item($this);
    }

    public function getClothingLayer()
    {
        if (!isset($this->data->flags)) {
            return "";
        }
        if (isset($this->data->flags["PERSONAL"])) {
            return "????????????";
        } elseif (isset($this->data->flags["SKINTIGHT"])) {
            return "??????";
        } elseif (isset($this->data->flags["WAIST"])) {
            return "??????";
        } elseif (isset($this->data->flags["OUTER"])) {
            return "??????";
        } elseif (isset($this->data->flags["BELTED"])) {
            return "??????";
        } elseif (isset($this->data->flags["AURA"])) {
            return "????????????";
        } else {
            return "??????";
        }
    }

    public function hasFlag($flag)
    {
        return isset($this->flags[$flag]);
    }

    public function getQualities()
    {
        return array_map(function ($quality) {
            return array(
                "quality" => $this->repo->getModel("Quality", $quality[0]),
                "level" => $quality[1],
            );
        }, $this->data->qualities);
    }

    public function qualityLevel($quality)
    {
        foreach ($this->data->qualities as $q) {
            if ($q[0] == $quality) {
                return $q[1];
            }
        }
    }

    public function getSlug()
    {
        $name = $this->data->name;
        if (is_object($name)) {
            if (isset($name->str)) {
                $name = $name->str;
            } else {
                $name = $name->str_sp;
            }
        }

        return str_replace(" ", "_", $name);
    }

    public function noise($ammo)
    {
        if (!$this->isGun) {
            return 0;
        }

        if (in_array($ammo->ammo_type, array('bolt', 'arrow', 'pebble', 'fishspear', 'dart'))) {
            return 0;
        }

        $ret = $ammo->damage;
        if (is_object($ret)) {
            $ret = $ret->amount;
        }
        $ret *= 0.8;
        if ($ret > 5) {
            $ret += 20;
        }
        $ret *= 1.5;

        return $ret;
    }

    public function getMaterials()
    {
        $materials = array(
            $this->material1,
        );
        if ($this->material2->id != "null") {
            $materials[] = $this->material2;
        }

        return $materials;
    }

    public function getHasFlags()
    {
        return count($this->flags) > 0;
    }

    public function getHasTechniques()
    {
        if (is_array($this->techniques)) {
            return count($this->techniques) > 0;
        }
        if (is_string($this->techniques)) {
            return true;
        }

        return false;
    }

    public function getDamage()
    {
        if (isset($this->data->damage)) {
            $damage = $this->data->damage;
            if (is_object($damage)) {
                $strval = '';
                if (isset($damage->amount)) {
                    $strval = $damage->amount;
                }
                if (isset($damage->constant_damage_multiplier)) {
                    $strval .= 'x'.$damage->constant_damage_multiplier;
                }
                if (isset($damage->damage_type)) {
                    $strval.=" ({$damage->damage_type})";
                }

                return $strval;
            } else {
                return $damage;
            }
        }
    }

    public function getDamagePerMove()
    {
        if (!$this->movesPerAttack) {
            return 0;
        }

        return number_format(($this->bashing + $this->cutting + $this->piercing) / ($this->movesPerAttack / 100.0), 2, ".", "");
    }

    public function getIsModdable()
    {
        if (isset($this->data->valid_mod_locations)) {
            if (is_array($this->data->valid_mod_locations)) {
                return count($this->data->valid_mod_locations) > 0;
            } else {
                return true;
            }
        }
        return false;
    }

    public function getIsBrewable()
    {
        return isset($this->data->brewable);
    }

    public function getBrewable()
    {
        $brewtime = $this->data->brewable->time;

        $brewresults = array("* ??????????????????????????????????????? $brewtime ???????????????");
        foreach ($this->data->brewable->results as $output) {
            $brewitem = $this->repo->getModel("Item", $output);
            $brewresults[] = '* ?????????????????????????????? <a href="'.route("item.view", array("id" => $brewitem->id)).'">'.$brewitem->name.'</a>';
        }

        $brewproducts = implode("<br>", $brewresults);

        return $brewproducts;
    }

    public function getIsGunMod()
    {
        return $this->type == "GUNMOD";
    }

    public function getIsPetArmor()
    {
        return $this->type == "PET_ARMOR" || isset($this->pet_armor_data);
    }

    public function getModGuns()
    {
        return $this->repo->allModels("Item", "gunmodGuns.{$this->data->id}");
    }

    public function getId()
    {
        return $this->data->id;
    }

    public function getCovers()
    {
        return array_map(function ($cover) {
            $model = $this->repo->getModel("Item", $cover);
            return $model->name;
        }, isset($this->data->covers) ? $this->data->covers : []);
    }

    public function getConstructionUses()
    {
        return $this->repo->allModels('Construction', "construction.{$this->data->id}");
    }

    public function getSourcePart()
    {
        return $this->repo->getModel("Item", $this->data->item);
    }

    public function getEncumbrance()
    {
        $result = 0;
        if (!isset($this->data->encumbrance)) {
            return 0;
        }
        if (is_numeric($this->data->encumbrance) && $this->data->encumbrance > 0) {
            $result = "";
            $foundvarsize = false;
            $enc = $this->data->encumbrance;
            // not sure why index number contains the flag values
            foreach ($this->data->flags as $indexnum => $flag) {
                if (!is_array($indexnum) && $indexnum == "VARSIZE") {
                    $foundvarsize = true;
                }
            }
            if ($foundvarsize == true) {
                $result = "<yellow>".$enc."</yellow> (?????????), <yellow>".max(floor($enc / 2), $enc - 10)."</yellow> (??????)";
            } else {
                $result = $enc;
            }
        }

        if ($this->data->max_encumbrance > 0) {
            $result = $result." ~ <yellow>".$this->data->max_encumbrance."</yellow>";
        }

        return $result;
    }

    public function getRangedDamage()
    {
        $inner = array();
        if (!isset($this->data->ranged_damage)) {
            return 0;
        }
        if (isset($this->data->ranged_damage_type)) {
            return $this->data->ranged_damage." ({$this->data->ranged_damage_type})";
        }
        if (!is_numeric($this->data->ranged_damage)) {
            if (is_array($this->data->ranged_damage)) {
                foreach ($this->data->ranged_damage as $indexnum => $damageunit) {
                    $inner[] = (is_numeric($damageunit->amount) ? $damageunit->amount : "").(isset($damageunit->damage_type) ? " (".$damageunit->damage_type.")" : "").(isset($damageunit->armor_multiplier) ? " (Armor multiplier $damageunit->armor_multiplier)" : "");
                }

                return implode(", ", $inner);
            } elseif (is_object($this->data->ranged_damage)) {
                $rd = $this->data->ranged_damage;

                return (is_numeric($rd->amount) ? $rd->amount : "").(isset($rd->damage_type) ? " (".$rd->damage_type.")" : "").(isset($rd->armor_multiplier) ? " (Armor multiplier $rd->armor_multiplier)" : "");
            }
        }

        return $this->data->ranged_damage;
    }

    public function getModName()
    {
        if (isset($this->data->modname)) {
            $id = $this->data->modname;
            return $this->repo->raw("modname.$id");
        }
    }

    public function getUsedby()
    {
        if (!isset($this->data->ammo_type)) {
            return array();
        }
        $guns = $this->repo->allModels("Item", "ammo.{$this->data->ammo_type}.usedby");
        return $guns;
    }

    public function getName()
    {
        $name = $this->repo->raw("item_multi.name.$this->id");
        if ($name) {
            return implode(" / ", $name);
        } else {
            return $this->rawname;
        }
    }

    public function getDescription()
    {
        if (!isset($this->data->description)) {
            return "";
        }
        if (is_object($this->data->description)) {
            return $this->data->description->str;
        }
        return $this->data->description;
    }

    public function getJson()
    {
        return json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function getTechniques()
    {
        if (isset($this->data->techniques)) {
            return array_map(
                function($id) {
                    $model = $this->repo->getMultiModelOrFail("Item", $id);
                    return "<stat>".$model[0]->name."</stat>???<info>".$model[0]->description."</info>";
                },
                $this->data->techniques
            );
        }
    }

    public function getAmmoModifier()
    {
        if (!isset($this->data->ammo_modifier)) {
            return;
        }
        $ammo_modifier = $this->data->ammo_modifier;
        if (!is_array($ammo_modifier)) {
            $ammo_modifier = array($ammo_modifier);
        }
        if (isset($this->data->ammo_modifier)) {
            return array_map(
                function ($id) {
                    $model = $this->repo->getModel("Item", $id);
                    return "<a href=\"".route("item.view", $id)."\">".$model->name."</a>";
                },
                $ammo_modifier
            );
        }
    }

    public function getModSkills()
    {
        if (isset($this->data->mod_targets)) {
            return implode(",", array_map(
                function ($id) {
                    try {
                        $item = $this->repo->getModelOrFail("Item", $id);
                        return '<a href="'.route("item.view", $id, $item->name).'">'.$item->name.'</a>';
                    } catch (\Exception $e) {
                        return '<a href="'.route("item.guns", $id, $id).'">'.$id.'</a>';
                    }
                },
                $this->data->mod_targets
            ));
        }
    }

    public function getDropFrom()
    {
        return array_map(
            function ($id) {
                try {
                    return $this->repo->getModel("Monster", $id);
                } catch (\Exception $e) {
                    return $this->repo->getModel("ItemGroup", $id);
                }
            },
            $this->repo->raw("item.dropfrom.$this->id")
        );
    }

    public function getHarvestFrom()
    {
        return array_map(
            function ($id) {
                return $this->repo->getModel("ItemGroup", $id);
            },
            $this->repo->raw("item.harvestfrom.$this->id")
        );
    }

    public function getbodyparts($data)
    {
        $trans = array(
            "TORSO" => "??????",
            "HEAD" => "??????",
            "EYES" => "??????",
            "MOUTH" => "??????",
            "ARM_L" => "??????",
            "ARM_R" => "??????",
            "HAND_L" => "??????",
            "HAND_R" => "??????",
            "LEG_L" => "??????",
            "LEG_R" => "??????",
            "FOOT_L" => "??????",
            "FOOT_R" => "??????",
        );
        if (isset($this->data->{$data})) {
            return implode(",", array_map(
                function($t) use($trans) {
                    $idx = strtoupper($t[0]);
                    return "{$trans[$idx]}???<yellow>{$t[1]}</yellow>???";
                },
                $this->data->{$data}
            ));
        }
    }

    public function getFuelOptions()
    {
        if (isset($this->data->fuel_options)) {
            return implode(",", array_map(
                function ($id) {
                    $model = $this->repo->getModel("Item", $id);
                    return '<a href="'.route("item.view", $id).'">'.$model->name.'</a>';
                },
                $this->data->fuel_options
            ));
        }
    }

    public function getFlagDescriptions()
    {
        if (!is_array($this->data->flags)) {
            return "";
        }
        $trans = array(
            "DIMENSIONAL_ANCHOR" => "??????????????? <good>??????</good> ?????????????????????",
            "PSYSHIELD_PARTIAL" => "??????????????? <good>????????????</good> ??? <info>??????????????????</info>???"
        );
        $ret = array();
        foreach ($this->data->flags as $flag => $v) {
            try {
                $raw = $this->repo->getMultiModelOrFail("Item", $flag);
                // echo "item.$flag".var_dump($raw[0]->data);
                if (isset($raw[0]->data->info)) {
                    $ret[] = "* ".$raw[0]->data->info."<br>";
                }
            } catch (\Exception $e) {
                if (array_key_exists($flag, $trans)) {
                    $ret[] = "* ".$trans[$flag]."<br>";
                }
            }
        }
        return implode("", $ret);
    }

    public function getFakeItem()
    {
        if(isset($this->data->fake_item)) {
            return $this->repo->getModel("Item", $this->data->fake_item);
        }
    }

    public function getBreaksInto()
    {
        if (isset($this->data->breaks_into)) {
            $breaks_into =  $this->data->breaks_into;
            if (is_string($breaks_into)) {
                return $breaks_into;
            } else {
                return array_map(
                    function ($item) {
                        return $this->repo->getModel("Item", $item->item);
                    },
                    $breaks_into
                );
            }
        }
    }

    public function getVitamins()
    {
        if(isset($this->data->vitamins)) {
            return implode("???", array_map(
                function($id) {
                    $model = $this->repo->getModel("Item", $id[0]);
                    return '<a href="'.route('special.vitamin', $id[0])."\">{$model->name}</a>???<yellow>{$id[1]}</yellow>%???";
                },
                $this->data->vitamins
            ));
        }
    }

    public function hasKey($key)
    {
        return isset($this->data->{$key});
    }

    public function getStorage()
    {
        if (!isset($this->data->pocket_data)) {
            return 0;
        }
        $sum = 0;
        foreach ($this->data->pocket_data as $pocket_data) {
            $sum += $pocket_data->max_contains_volume ?? 0;
        }
        return $sum;
    }

    public function getBookData()
    {
        $book_data = $this->data->book_data ?? NULL;
        if(!($book_data && isset($book_data->martial_art))) {
            return NULL;
        }
        $item = $this->repo->getModel("Item", $book_data->martial_art);
        return $item->name;
    }

    public function getReinforcable()
    {
        $materials = $this->getMaterials();
        foreach ($materials as $material) {
            if ($material->reinforces ?? false) {
                return true;
            }
        }
        return false;
    }

    public function getConductive()
    {
        if ($this->hasFlag("CONDUCTIVE")) {
            return true;
        }
        if ($this->hasFlag("NONCONDUCTIVE")) {
            return false;
        }
        $materials = $this->getMaterials();
        foreach ($materials as $material) {
            if (($material->elec_resist ?? 0) < 1) {
                return true;
            }
        }
        return false;
    }

    public function getRotSpawn()
    {
        if (!isset($this->data->rot_spawn)) {
            return NULL;
        }
        $group = $this->repo->getModel("MonsterGroup", $this->data->rot_spawn);
        return array_map(
            function ($mon) {
                $model = $this->repo->getModel("Monster", $mon->monster);
                $mon->monster = $model;
                return $mon;
            },
            $group->monsters
        );
    }

    public function get_item_restriction($idx)
    {
        if (isset($this->data->pocket_data[$idx]->item_restriction)) {
            return array_map(
                function ($id) {
                    return $this->repo->getModel("Item", $id);
                },
                $this->data->pocket_data[$idx]->item_restriction
            );
        } else {
            return array();
        }
    }

    public function get_ammo_restriction($idx)
    {
        $ret = array();
        $ammo_restriction = $this->data->pocket_data[$idx]->ammo_restriction;
        if (isset($ammo_restriction)) {
            foreach ($ammo_restriction as $ammo => $count) {
                $ammolist = $this->repo->allModels("Item", "ammo.$ammo");
                $ret[] = (object)array("count" => $count, "ammo" => $ammolist);
            }
        }
        return $ret;
    }

    public function effective_dps($mon)
    {
        $hits_by_accuracy = array(
            0,    1,   2,   3,   7, // -20 to -16
            13,   26,  47,   82,  139, // -15 to -11
            228,   359,  548,  808, 1151, // -10 to -6
            1587, 2119, 2743, 3446, 4207, // -5 to -1
            5000,  // 0
            5793, 6554, 7257, 7881, 8413, // 1 to 5
            8849, 9192, 9452, 9641, 9772, // 6 to 10
            9861, 9918, 9953, 9974, 9987, // 11 to 15
            9993, 9997, 9998, 9999, 10000 // 16 to 20
        );
        $mon_dodge = $mon->dodge;
        $base_hit = 8 / 4 + (4 / 3) + (4 / 2) + $this->data->cib_to_hit;
        $base_hit *= max(0.25, 1 - 20 / 100.0);
        $mon_defense = $mon->dodge + 0 / 5.0;
        $hit_trials = 10000.0;
        $rng_mean = max(min(intval($base_hit - $mon_defense), 20), -20) + 20;

        $num_all_hits = $hits_by_accuracy[$rng_mean];
        $rng_high_mean = max(min(intval($base_hit - 1.5 * $mon->dodge), 20), -20) + 20;
        $rng_high_hits = $hits_by_accuracy[$rng_high_mean] * $num_all_hits / $hit_trials;
    }

    // FIXME: skill ?????????????????????????????????????????????????????????????????????
    public function getSkill()
    {
        if (!isset($this->data->skill)) {
            return NULL;
        }

        $skill = $this->data->skill;
        if(preg_match('/[^\x20-\x7e]/', $skill)) {
            return $skill;
        } else {
            return $this->repo->getModel("Item", $skill)->name;
        }
    }

    public function getMinSkills()
    {
        if (!isset($this->data->min_skills))
            return NULL;
        return array_map(function ($skill) {
            return [
                $this->repo->getModel("Item", $skill[0])->name,
                $skill[1],
            ];
        }, $this->data->min_skills);
    }

    public function getRevertTo()
    {
        if (!isset($this->data->revert_to))
            return NULL;
        return $this->repo->getModel("Item", $this->data->revert_to);
    }
}
