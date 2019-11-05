<?php

namespace viv;

class BrandsParser extends Parser
{
    public function parse($data = null)
    {
        $brands = [];
        foreach ($this->dom->find('#cat_menu > ul > li') as $key => $li) {
            if($key == 1 || $key == 2) {
                foreach ($li->find('.subbrands a') as $a) {
                    $brands[] = [
                        'name' => $this->clearName($a->getAttribute('title')),
                        'src' => $this->getFullUrl($a->find('img')[0]->getAttribute('src'))
                    ];
                }
            }
        }
        return $brands;
    }

    public function clearName($name) {
        return trim(str_replace('Фотообои', '', str_replace('Обои', '', $name)));
    }

    public function exists($name) {
        $query = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer WHERE name = '{$name}'");
        return $query->row;
    }
}