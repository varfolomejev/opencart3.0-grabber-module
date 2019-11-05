<?php

namespace viv;

class ProductsParser extends Parser
{
    public $productModel;

    public $filterModel;

    public function parse($data =  null) {
        return $this->{$data['action']}($data);
    }

    public function start() {
        $categories = [];
        foreach ($this->dom->find('#cat_menu > ul > li') as $key => $li) {
            if($key == 1 || $key == 2) {
                foreach ($li->find('.subcats a') as $a) {
                    $categories[] = [
                        'fullName' => $a->getAttribute('title'),
                        'name' => trim($this->clearName($a->getAttribute('title'))),
                        'href' => $this->getFullUrl($a->getAttribute('href')),
                    ];
                }
            }
        }
        return $categories;
    }

    public function startParse($data) {
        $res = [];
        $category = $this->getSubCategoryIdByName($data['name']);
        $res['categoryId'] = $category['category_id'];
        $res['parentCategoryId'] = $category['parent_id'];

        $paginationLinks = $this->dom->find('.paging a');
        if(count($paginationLinks)) {
            $res['lastPaginationUrl'] = $this->getFullUrl($paginationLinks[count($paginationLinks) - 2]->getAttribute('href'));
        }

        foreach ($this->dom->find('#params li') as $li) {
            $label = trim($li->find('label')[0]->innerHtml);
            if($label == 'Цена, грн.:' || $label == 'Обои' || $label == 'Подкаталоги меню' || $label == 'Фотообои') {
                continue;
            }
            $filterGroup = $this->filterGroupExists($label);
            if(!$filterGroup) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "filter_group` SET sort_order = 0");
                $filterGroup['filter_group_id'] = $this->db->getLastId();
                foreach ([1 => $label, 2 => $label, 3 => $label] as $language_id => $name) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "filter_group_description SET filter_group_id = '" . (int)$filterGroup['filter_group_id'] . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($name) . "'");
                }
            }
            $res['filterGroups'][$filterGroup['filter_group_id']] = $label;
        }

        return $res;
    }

    public function pageParse($data) {
        $res = [];
        foreach ($this->dom->find('.catalog-content .product') as $product) {
            $res['products'][] = $this->getFullUrl($product->find('a')[0]->getAttribute('href'));
        }
        $paginationLinks = $this->dom->find('.paging a');
        if(count($paginationLinks)) {
            $res['nextPaginationUrl'] = $this->getFullUrl($paginationLinks[count($paginationLinks) - 1]->getAttribute('href'));
        }
        return $res;
    }

    public function pageProduct($data) {

    }

    public function clearName($name) {
        return trim(str_replace('Фотообои', '', str_replace('Обои', '', $name)));
    }

    public function filterGroupExists($name) {
        $query = $this->db->query("SELECT filter_group_id FROM " . DB_PREFIX . "filter_group_description WHERE name = '{$name}' LIMIT 1");
        return $query->row;
    }

    public function exists($name, $parent) {
        $query = $this->db->query("SELECT c.category_id FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON cd.category_id = c.category_id WHERE cd.name = '{$name}' AND c.parent_id = {$parent}");
        return $query->row;
    }

    public function getSubCategoryIdByName($name) {
        $query = $this->db->query("SELECT c.category_id, c.parent_id FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON cd.category_id = c.category_id WHERE cd.name = '{$name}' AND c.parent_id != 0");
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