<?php

namespace Presenters;

class Item extends \Robbo\Presenter\Presenter
{
    public function presentSymbol()
    {
        $symbol = $this->object->symbol;
        if ($symbol == " ") {
            return "&nbsp;";
        }

        return "<span style=\"color: $this->color\">".htmlspecialchars($symbol)."</span>";
    }

    public function presentRawName()
    {
        return ucfirst($this->object->rawname);
    }

    public function presentVolume()
    {
        return $this->object->volume === null ? "N/A" : $this->object->volume;
    }

    public function presentWeight()
    {
        $weight = $this->object->weight;
        if ($weight === null) {
            return;
        }

        return number_format($weight / 453.6, 2);
    }

    public function presentWeightMetric()
    {
        $weight = $this->object->weight;
        if ($weight === null) {
            return;
        }

        return number_format($weight / 1000, 2);
    }

    public function presentBashing()
    {
        return $this->object->bashing ?: "0";
    }

    public function presentCutting()
    {
        return $this->object->cutting ?: "0";
    }

    public function presentToHit()
    {
        return $this->object->to_hit ?: "N/A";
    }

    public function presentMovesPerAttack()
    {
        return $this->object->moves_per_attack ?: "N/A";
    }

    public function presentRecipes()
    {
        return array_map(function ($recipe) {
            return $recipe->getPresenter();
        }, $this->object->recipes);
    }

    public function presentDisassembly()
    {
        return array_map(function ($recipe) {
            return $recipe->getPresenter();
        }, $this->object->disassembly);
    }

    public function presentDeconstructFrom()
    {
        return array_unique(array_map(function ($n) {
            return $n->getPresenter()->name;
        }, $this->object->DeconstructFrom));
    }

    public function presentBashFromTerrain()
    {
        return array_unique(array_map(function ($n) {
            return $n->getPresenter()->name;
        }, $this->object->bashFromTerrain));
    }

    public function presentMaterials()
    {
        return implode(", ", array_map(function ($material) {
            return '<a href="'.route("item.materials", $material->id).'">'.$material->name.'</a>';
        }, $this->object->materials));
    }

    public function presentFlags()
    {
        $invert = array_flip($this->object->flags);

        if (empty($invert)) {
            return "None";
        }

        return implode(", ", array_map(function ($flag) {
            return '<a href="'.route("item.flags", $flag).'">'.$flag.'</a>';
        }, $invert));
    }

    public function presentFeatureLabels()
    {
        $badges = array();
        if ($this->count("toolFor")) {
            $badges[] = '<a href="'.route("item.recipes", $this->object->id).'"><span class="label label-success">??????: '.$this->count("toolFor").'</span></a>';
        }
        if ($this->count("disassembly")) {
            $badges[] = '<a href="'.route("item.disassemble", $this->object->id).'"><span class="label label-info">??????</span></a>';
        }
        if ($this->count("recipes")) {
            $badges[] = '<a href="'.route("item.craft", $this->object->id).'"><span class="label label-default">??????: '.$this->count("recipes").'</span></a>';
        }
        if ($this->count("construction")) {
            $badges[] = '<a href="'.route("item.construction", $this->object->id).'"><span class="label label-warning">??????: '.$this->count("construction").'</span></a>';
        }
        if ($this->count("uncraftToolFor")) {
            $badges[] = '<span class="label label-warning">????????????: '.$this->count("uncraftToolFor").'</span>';
        }
        if ($this->modname !== null) {
            $badges[] = '<span class="label label-warning">mod</span>';
        }
        if ($this->object->override == true) {
            $badges[] = '<span class="label label-warning">??????????????????</span>';
        }
        if (is_string($this->object->abstract)) {
            $badges[] = '<span class="label label-danger">JSON template/abstract</span>';
        }

        return implode(" ", $badges);
    }

    public function presentModLabel()
    {
        $badges = array();
        if ($this->modname != null) {
            $badges[] = '<span class="label label-warning">'.$this->modname.'</span>';
        }

        return implode(" ", $badges);
    }

    public function presentCraftingRecipes()
    {
        $recipes = array();
        foreach ($this->object->learn as $recipe) {
            $recipes[] = '<a href="'.route('item.view', $recipe->result->id).'">'.$recipe->result->name.'</a>';
        }

        return implode(", ", $recipes);
    }

    public function presentCovers()
    {
        if (!$this->object->covers) {
            return "none";
        }

        return implode(", ", array_map(function ($cover) {
            return '<a href="'.route('item.armors', $cover).'">'.$cover.'</a>';
        }, $this->object->covers));
    }

    public function presentSpoilsIn()
    {
        if (is_numeric($this->object->spoils_in)) {
            if ($this->object->spoils_in < 24) {
                return $this->object->spoils_in." ??????";
            }

            $weeks = 0;
            $days = floor($this->object->spoils_in / 24);
            $hours = $this->object->spoils_in % 24;
            while ($days > 6) {
                $weeks++;
                $days -= 7;
            }
            $result = "";
            if ($weeks > 0) {
                $result = $result."$weeks ??? ";
            }
            if ($days > 0) {
                $result = $result."$days ??? ";
            }
            if ($hours > 0) {
                $result = $result."$hours ?????? ";
            }
//            return ($this->object->spoils_in / 24)." days";
            return $result;
        }
        $result = str_replace(
            array("weeks", "week", "days", "day", "hours", "hour", "d"),
            array("???", "???", "???", "???", "??????", "??????", "???"),
            $this->object->spoils_in
        );
        return $result;
    }

    public function presentStim()
    {
        return ($this->object->stim * 5)." ??????";
    }

    public function presentValidModLocations()
    {
        $ret = array();
        $parts = $this->object->valid_mod_locations;
        foreach ($parts as $part) {
            $skill = $this->object->skill;
            $ret[] = "$part[1] ".'<a href="'.route("item.gunmods", array($skill, $part[0])).'">'.$part[0].'</a>';
        }

        return implode("; ", $ret);
    }

    public function presentClipSizeModifier()
    {
        return sprintf("%+d", $this->object->clip_size_modifier);
    }

    public function presentDispersionModifier()
    {
        return sprintf("%+d", $this->object->dispersion_modifier);
    }

    public function presentRangeModifier()
    {
        return sprintf("%+d", $this->object->range_modifier);
    }

    public function presentHandlingModifier()
    {
        return sprintf("%+d", $this->object->handling_modifier);
    }

    public function presentLoudnessModifier()
    {
        return sprintf("%+d", $this->object->loudness_modifier);
    }

    public function presentDamageModifier()
    {
        $damage = $this->object->damage_modifier;
        if (is_object($damage)) {
            return sprintf("%+d???%s???", $damage->amount, $damage->damage_type);
        } else {
            return sprintf("%+d", $damage);
        }
    }

    public function presentBurstModifier()
    {
        return sprintf("%+d", $this->object->burst_modifier);
    }

    public function presentRecoilModifier()
    {
        return sprintf("%+d", $this->object->recoil_modifier);
    }

    public function presentTechniques()
    {
        $techs = (array) $this->object->techniques;
        if (empty($techs)) {
            return "";
        }

        return implode(", ", $techs);
    }

    public function presentSourcePart()
    {
        if ($this->object->item === null) {
            return "(unknown)";
        }
        $sourcepart = $this->object->sourcepart;

        return '<a href="'.route("item.view", array("id" => $sourcepart->id)).'">'.$sourcepart->name.'</a>';
    }

    public function presentUsedBy()
    {
        $ret = array();
        foreach ($this->object->usedby as $usedby) {
            $ret[] = '<a href="'.route("item.view", array("id" => $usedby->id)).'">'.$usedby->name.'</a>';
        }
        return implode(", ", $ret);
    }

    public function presentJson()
    {
        return '<pre><code class="language-json">'.$this->object->json.'</code></pre>';
    }

    public function presentMinSkills()
    {
        if (!$this->object->min_skills) {
            return;
        }
        $ret = array();
        foreach ($this->object->min_skills as $skill) {
            $ret[] = $skill[0]."???$skill[1]???";
        }
        return implode(',', $ret);
    }

    function normalize_price($data)
    {
        if (strpos($data, "USD")) {
            return floatval($data) * 1.0;
        } else if (strpos($data, "kUSD")) {
            return floatval($data) * 1000.0;
        }
        return floatval($data) / 100.0;
    }

    public function presentPrice()
    {
        if (!isset($this->object->price)) {
            $price = $this->normalize_price($this->object->price);
            return round($price * floatval($this->object->count ?? 1) / floatval($this->object->stack_size ?? 1), 2);
        }
    }

    public function presentPricePostapoc()
    {
        if (!isset($this->object->price_postapoc)) {
            $price = $this->normalize_price($this->object->price_postapoc);
            return round($price * floatval($this->object->count ?? 1) / floatval($this->object->stack_size ?? 1), 2);
        }
    }

    public function presentModes()
    {
        if ($this->object->modes != NULL) {
            $ret = array();
            foreach ($this->object->modes as $mode) {
                switch ($mode[1]) {
                    case 'semi-auto':
                        $ret[] = "????????????{$mode[2]}???";
                        break;
                    case 'auto':
                        $ret[] = "????????????{$mode[2]}???";
                        break;
                    default:
                        $ret[] = "?????????{$mode[2]}???";
                        break;
                }
            }
            return implode("???", $ret);
        }
    }

    public function presentDropFrom()
    {
        $ret = implode(", ", array_map(
            function ($drop) {
                if ($drop->type == "MONSTER") {
                    return '<a href="'.route('monster.view', $drop->id).'">'.$drop->nicename.'</a>';
                } else {
                    return '<a href="'.route('special.itemgroup', $drop->id).'">'.$drop->id.'</a>';
                }
            },
            $this->object->dropfrom
        ));
        if ($ret == "") {
            return;
        } else {
            return "????????????$ret<br>";
        }
    }

    public function presentHarvestFrom()
    {
        $ret = implode(", ", array_map(
            function ($drop) {
                return '<a href="'.route('special.itemgroup', $drop->id).'">'.$drop->id.'</a>';
            },
            $this->object->harvestfrom
        ));
        if ($ret == "") {
            return;
        } else {
            return "????????????$ret<br>";
        }
    }

    public function presentBreaksInto()
    {
        $breaks_into = $this->object->breaks_into;
        if (is_array($breaks_into)) {
            return implode("???", array_map(function ($entry) {
                return '<a href="' . route("item.view", $entry->id) . '">' . $entry->name . '</a>';
            }, $breaks_into));
        } elseif (is_string($breaks_into)) {
            return '<a href="' . route('special.itemgroup', $breaks_into) . '">' . $breaks_into . '</a>';
        } else {
            return;
        }
    }

    public function presentLongestSide()
    {
        $v = $this->object->longest_side;
        if ($v === NULL) {
            $v = round(pow($this->object->volume * 1000, 1.0 / 3.0));
            return "<yellow>$v</yellow> ??????";
        } else {
            $s = "<yellow>".floatval($v)."</yellow> ";
            if (strpos($v, "mm") !== FALSE) {
                $s .= "??????";
            } else if (strpos($v, "cm") !== FALSE) {
                $s .= "??????";
            } else if (strpos($v, "meter") !== FALSE) {
                $s .= "???";
            }
            return $s;
        }
    }

    public function presentRotSpawn()
    {

        $monsters = $this->object->rot_spawn;
        return implode("???", array_map(
            function ($mon) {
                $ret = "";
                $name = $mon->monster->name;
                if (is_object($name)) {
                    $name = $name->str;
                }
                $freq = $mon->freq / 10;
                $ret .= '<a href="'.route("monster.view", $mon->monster->id).'">'.$name."</a>";
                $ret .= " ???{$freq}%???";
                return $ret;
            },
            $monsters
        ));
    }

    public function get_item_restriction($idx)
    {
        return implode("???", array_map(
            function ($item) {
                return '<a href="'.route("item.view", $item->id).'">'.$item->name."</a>";
            },
            $this->object->get_item_restriction($idx)
        ));
    }

    public function get_ammo_restriction($idx)
    {
        $ret = array();
        foreach ($this->object->get_ammo_restriction($idx) as $ammos) {
            $ret[] = "?????????<yellow>{$ammos->count}</yellow> ??? ". implode(" ??? ", array_map(
                function ($ammo) {
                    return '<a href="'.route("item.view", $ammo->id).'">'.$ammo->name."</a>";
                },
                $ammos->ammo
            ));
        }
        return implode("<br>", $ret);
    }
}
