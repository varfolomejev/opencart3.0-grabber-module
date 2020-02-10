<?php

namespace viv;

class ProductsParser extends Parser
{
    public $productModel;

    public $attributeModel;

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
            $res['filterGroups'][$label] = $filterGroup['filter_group_id'];
        }

        return $res;
    }

    public function pageParse($data) {
        $res = [];
        foreach ($this->dom->find('.catalog-content .product') as $product) {
            $res['products'][] = $this->getFullUrl($product->find('.name_product a', 0)->getAttribute('href'));
        }
        $paginationLinks = $this->dom->find('.paging a');
        if(count($paginationLinks)) {
            foreach ($paginationLinks as $a) {
                $res['links'][] = $this->getFullUrl($a->getAttribute('href'));
                if(!in_array($this->getFullUrl($a->getAttribute('href')), $data['paginationUrls'])) {
                    $res['nextPaginationUrl'] = $this->getFullUrl($a->getAttribute('href'));

                }
            }
        }
        return $res;
    }

    public function pageProduct($data) {
        $productData = [
            'product_store' => [0],
            'minimum' => 1,
            'subtract' => 1,
            'stock_status_id' => 5,
            'shipping' => 1,
            'date_available' => date('Y-m-d'),
            'length' => 0,
            'width' => 0,
            'height' => 0,
            'length_class_id' => 1,
            'weight' => 0,
            'weight_class_id' => 1,
            'status' => 1,
            'sort_order' => 0,
            'upc' => '',
            'ean' => '',
            'jan' => '',
            'isbn' => '',
            'mpn' => '',
            'location' => '',
            'quantity' => 0,
            'manufacturer_id' => '',
            'points' => 0,
            'tax_class_id' => 0,
            'currency_id' => 0,
            'price' => 0,
        ];
        $brandName = '';
        // Status
        foreach($this->dom->find('.productInStatuses img') as $img) {
            $attribute = $this->getAttributeByName('Статус');
            $productData['product_attribute'][] = [
                'name' => 'Статус',
                'attribute_id' => $attribute['attribute_id'],
                'product_attribute_description' => [
                    1 => ['text' => $img->getAttribute('alt')],
                    2 => ['text' => $img->getAttribute('alt')],
                    3 => ['text' => $img->getAttribute('alt')],
                ]
            ];
        }

        // Text
        $productData['name'] = '';
        foreach ($this->dom->find('#breadcrumbs li') as $key => $li) {
            $productData['name'] = $li->text;
        }
        $h1 = $this->dom->find('h1', 0);
        if(strlen($h1->text)) {
            $filterGroup = false;
//            $titleArr = explode(' ', trim($h1->text));
//            $productData['name'] = $titleArr[count($titleArr) - 1];
            $brandCollection = trim(str_replace('Обои', '', str_replace($productData['name'], '', $h1->text)));
            if(strlen($brandCollection)) {
                $brandName = false;
                $collectionName = false;
                if($this->dom->find('.characteristic p')) {
                    foreach ($this->dom->find('.characteristic p') as $p) {
                        if(!$p->find('strong')[0]) {
                            continue;
                        }
                        $attributeName = trim(str_replace(':', '', $p->find('strong')[0]->innerHtml));
                        if($attributeName == 'Бренд') {
                            $imgName = basename($p->find('img')[0]->getAttribute('src'));
                            foreach ($this->dom->find('#cat_menu > ul > li') as $key => $li) {
                                if($key == 1 || $key == 2) {
                                    foreach ($li->find('.subbrands a') as $a) {
                                        $brandImageName = basename($a->find('img')[0]->getAttribute('src'));
                                        if($imgName == $brandImageName) {
                                            $brandName = $this->clearName($a->getAttribute('title'));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

//                foreach ($data['filterGroups'] as $filterGroupName => $filterGroupId) {
//                    $clearedFilterName = trim(str_replace('Коллекции', '', $filterGroupName));
//                    if(strpos($brandCollection, $clearedFilterName) === 0) {
//                        $brandName = $clearedFilterName;
//                        $collectionName = trim(str_replace($brandName, '', $brandCollection));
//                        break;
//                    }
//                }

                if($brandName) {
                    $brand = $this->brandExists($brandName);
                    $productData['manufacturer_id'] = $brand['manufacturer_id'];
                    foreach ($data['filterGroups'] as $filterGroupName => $filterGroupId) {
                        if(strpos($filterGroupName, $brandName) !== false) {
                            $filterGroup = $filterGroupId;
                        }
                    }
                    $collectionName = trim(str_replace($brandName, '', $brandCollection));
                }

                if($collectionName) {
                    $filter = $this->getFilterByName($filterGroup, $collectionName);
                    $productData['product_filter'][] = $filter['filter_id'];
                    $productData['product_attribute'][] = [
                        'name' => 'Коллекция',
                        'attribute_id' => 46,
                        'product_attribute_description' => [
                            1 => ['text' => $collectionName],
                            2 => ['text' => $collectionName],
                            3 => ['text' => $collectionName],
                        ]
                    ];
                }
            }
        }
//        foreach ($this->dom->find('#breadcrumbs li') as $key => $li) {
//            $filterGroup = false;
//            if($key == 3) {
//                $brandName = trim($li->find('span')->text);
//                $brand = $this->brandExists($brandName);
//                $productData['manufacturer_id'] = $brand['manufacturer_id'];
//                foreach ($data['filterGroups'] as $filterGroupName => $filterGroupId) {
//                    if(strpos($filterGroupName, $brandName) !== false) {
//                        $filterGroup = $filterGroupId;
//                    }
//                }
//            } else if($key == 4) {
//                $filter = $this->getFilterByName($filterGroup, trim($li->find('span')->text));
//                $productData['product_filter'][] = $filter['filter_id'];
//            } else if ($key == 5) {
//                $productData['name'] = $li->text;
//            }
//        }

        // Price
        $priceSpans = $this->dom->find('.priceText span');
        foreach ($priceSpans as $priceSpan) {
            if($priceSpan->getAttribute('itemprop') == 'price') {
                $productData['price'] = floatval(str_replace(' ', '', $priceSpan->text));
            }
        }
        foreach($this->dom->find('.priceText del') as $del) {
            $dateEnd = new \DateTime();
            $dateEnd->modify('+1 year');
            $productData['product_discount'][] = [
                'customer_group_id' => 1,
                'quantity' => 999,
                'priority' => 0,
                'price' => $productData['price'],
                'date_start' => $productData['date_available'],
                'date_end' => $dateEnd->format('Y-m-d'),
            ];
            $productData['price'] = floatval(str_replace(' ', '', $del->text));
        }

        foreach ($this->dom->find('p') as $p) {
            if(strpos($p->innerHtml, 'в наличии') !== false) {
                $productData['quantity'] = 999;
                $productData['stock_status_id'] = 7;
            }
        }

        // Images
        foreach ($this->dom->find('#bx-pager a') as $key => $a) {
            $imageUrl = $this->getFullUrl($a->find('img')[0]->getAttribute('src'));
            $image = $this->downloadFile($imageUrl, 'catalog/products/'. basename($imageUrl));
            $productData['product_image'][] = ['image' => $image, 'sort_order' => $key];
        }

        // Tags
        $tags = [];
        foreach ($this->dom->find('.tags-cloud a') as $a) {
            $tags[] = $a->text;
        }

        // Attributes
        if($this->dom->find('.characteristic p')) {
            foreach ($this->dom->find('.characteristic p') as $a) {
                if(!$a->find('strong')[0]) {
                    continue;
                }
                $attributeName = trim(str_replace(':', '', $a->find('strong')[0]->innerHtml));
                if($t = $a->find('span', 0)) {
                    $value = $t->text;
                } else if ($t = $a->find('a', 0)) {
                    $value = $t->text;
                } else {
                    $value = trim(str_replace($a->find('strong')[0]->outerHtml, '', $a->innerHtml));
                }
                if($attributeName == 'Бренд') {
                    continue;
                }
                if($attributeName == 'Артикул') {
                    $productData['model'] = $value;
                    $productData['sku'] = $value;
                    continue;
                }
                $attribute = $this->getAttributeByName($attributeName);
                $productData['product_attribute'][] = [
                    'name' => $attributeName,
                    'attribute_id' => $attribute['attribute_id'],
                    'product_attribute_description' => [
                        1 => ['text' => $value],
                        2 => ['text' => $value],
                        3 => ['text' => $value],
                    ]
                ];
                if(isset($data['filterGroups'][$attributeName])) {
                    $filter = $this->getFilterByName($data['filterGroups'][$attributeName], $value);
                    $productData['product_filter'][] = $filter['filter_id'];
                }
            }
        }

        // Description
        foreach ([1,2,3] as $languageId) {
            $productData['product_description'][$languageId] = [
                'name' => $productData['name'],
                'description' => '',
                'tag' => implode(',', $tags),
                'meta_title' => $productData['name'],
                'meta_description' => '',
                'meta_keyword' => '',
                'video' => '',
                'tab_title' => '',
                'html_product_tab' => '',
            ];
        }

        if(isset($productData['product_image'][0])) {
            $productData['image'] = $productData['product_image'][0]['image'];
            unset($productData['product_image'][0]);
        }

        $productData['product_category'] = [$data['parentCategoryId'], $data['categoryId']];
        $slug = $this->slugify($brandName . '-' . $productData['name']);
        $productData['product_seo_url'][0] = [
            1 => $slug,
            2 => $slug,
            3 => $slug,
        ];

        $results = [
            'status' => 'ok',
            'action' => 'create',
//            'product' => $slug,
        ];
        $product = $this->exists($productData['model']);
        if(!$product) {
            $product_id = $this->productModel->addProduct($productData);
            $product['product_id'] = $product_id;
        } else {
            $results['action'] = 'edit';
            $this->productModel->editProduct($product['product_id'], $productData);
        }
//        print_r($product);exit;
//        return $productData;
        $results['product'] = $product['product_id'];
        return $results;
    }

    public function clearName($name) {
        return trim(str_replace('Фотообои', '', str_replace('Обои', '', $name)));
    }

    public function filterGroupExists($name) {
        $query = $this->db->query("SELECT filter_group_id FROM " . DB_PREFIX . "filter_group_description WHERE name = '" . $this->db->escape($name) . "' LIMIT 1");
        return $query->row;
    }

    public function exists($model) {
        $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE model = '" . $this->db->escape($model) . "' LIMIT 1");
        return $query->row;
    }

    public function getSubCategoryIdByName($name) {
        $query = $this->db->query("SELECT c.category_id, c.parent_id FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON cd.category_id = c.category_id WHERE cd.name = '" . $this->db->escape($name) . "' AND c.parent_id != 0");
        return $query->row;
    }

    public function getAttributeByName($name) {
        $query = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute_description WHERE name = '" . $this->db->escape($name) . "' LIMIT 1");
        if(!$query->row) {
            $query->row['attribute_id'] = $this->attributeModel->addAttribute([
                'attribute_group_id' => 22,
                'sort_order' => 0,
                'attribute_description' => [
                    1 => ['name' => $name],
                    2 => ['name' => $name],
                    3 => ['name' => $name],
                ]]);
        }
        return $query->row;
    }

    public function getFilterByName($filterGroupId, $name) {
        $query = $this->db->query("SELECT filter_id  FROM " . DB_PREFIX . "filter_description WHERE name = '" . $this->db->escape($name) . "' LIMIT 1");
        if(!$query->row) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "filter SET filter_group_id = '" . (int)$filterGroupId . "', sort_order = 0");
            $filter_id = $this->db->getLastId();
            foreach ([1,2,3] as $languageId) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "filter_description SET filter_id = '" . (int)$filter_id . "', language_id = '" . (int)$languageId . "', filter_group_id = '" . (int)$filterGroupId . "', name = '" . $this->db->escape($name) . "'");
            }
            $query->row['filter_id'] = $filter_id;
        }
        return $query->row;
    }

    public function brandExists($name) {
        $query = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer WHERE name = '" . $this->db->escape($name) . "'");
        return $query->row;
    }
}