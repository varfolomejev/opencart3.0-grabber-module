<?php

namespace viv;

class CategoriesParser extends Parser
{
    public function parse($data = null)
    {
        $categories = [];
        foreach ($this->dom->find('#cat_menu > ul > li') as $key => $li) {
            if($key == 1 || $key == 2) {
                $a = $li->find('.cat_img a')[0];
                $mainCategory = [
                    'name' => trim($a->getAttribute('title')),
                    'src' => $this->getFullUrl($a->find('img')[0]->getAttribute('src')),
                    'children' => [],
                ];
                foreach ($li->find('.subcats a') as $a) {
                    $mainCategory['children'][] = [
                        'name' => trim($this->clearName($a->getAttribute('title'))),
                        'src' => null,
                    ];
                }
                $categories[] = $mainCategory;
            }
        }
        return $categories;
    }

    public function clearName($name) {
        return trim(str_replace('Фотообои', '', str_replace('Обои', '', $name)));
    }

    public function exists($name, $parent) {
        $query = $this->db->query("SELECT c.category_id FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON cd.category_id = c.category_id WHERE cd.name = '{$name}' AND c.parent_id = {$parent}");
        return $query->row;
    }

    public function getCategoryData($category, $parentId, $image = null) {
        $slug = $this->slugify($category['name']);
        return [
            'parent_id' => $parentId,
            'top' => 0,
            'column' => 1,
            'sort_order' => 0,
            'status' => 1,
            'image' => $image,
            'category_description' => [
                1 => [
                    'name' => $category['name'],
                    'description' => '',
                    'meta_title' => $category['name'],
                    'meta_description' => '',
                    'meta_keyword' => '',
                ],
                2 => [
                    'name' => $category['name'],
                    'description' => '',
                    'meta_title' => $category['name'],
                    'meta_description' => '',
                    'meta_keyword' => '',
                ],
                3 => [
                    'name' => $category['name'],
                    'description' => '',
                    'meta_title' => $category['name'],
                    'meta_description' => '',
                    'meta_keyword' => '',
                ],
            ],
            'category_store' => [0],
            'category_seo_url' => [
                0 => [
                    1 => $slug,
                    2 => $slug,
                    3 => $slug,
                ]
            ],
            'category_layout' => [0],
        ];
    }
}