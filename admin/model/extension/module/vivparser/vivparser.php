<?php
include dirname(__FILE__) . "/../../../../../vendor/autoload.php";
include dirname(__FILE__) . "/Parser.php";
include dirname(__FILE__) . "/BrandsParser.php";
include dirname(__FILE__) . "/CategoriesParser.php";
include dirname(__FILE__) . "/ProductsParser.php";

class Modelextensionmodulevivparservivparser extends Model {

    private $siteUrl = 'https://euro-style.kiev.ua';

    public function parseBrands($manufacturerModel) {
        $parser = new \viv\BrandsParser($this->siteUrl, $this->db);
        $brandsData = $parser->parse();
        foreach ($brandsData as &$brandData) {
            if(!$parser->exists($brandData['name'])) {
                $image = $parser->downloadFile($brandData['src'], 'catalog/brands/' . basename($brandData['src']));
                $slug = $parser->slugify($brandData['name']);
                $id = $manufacturerModel->addManufacturer([
                    'name' => $brandData['name'],
                    'manufacturer_store' => [0],
                    'image' => $image,
                    'sort_order' => 0,
                    'manufacturer_seo_url' => [
                        0 => [
                            1 => $slug,
                            2 => $slug,
                            3 => $slug,
                        ]
                    ]
                ]);
                $brandData['id'] = $id;
            }
        }
        return $brandsData;
    }

    public function parseCategories($categoryModel) {
        $parser = new \viv\CategoriesParser($this->siteUrl, $this->db);
        $categories = $parser->parse();

        foreach ($categories as &$category) {
            $parent = $parser->exists($category['name'], 0);
            if(!$parent) {
                $image = $parser->downloadFile($category['src'], 'catalog/categories/' . basename($category['src']));
                $id = $categoryModel->addCategory($parser->getCategoryData($category, 0, $image));
                $category['id'] = $id;
            } else {
                $category['id'] = $parent['category_id'];
            }
            foreach ($category['children'] as &$child) {
                $childExists = $parser->exists($child['name'], $category['id']);
                if(!$childExists) {
                    $id = $categoryModel->addCategory($parser->getCategoryData($child, $category['id']));
                    $child['id'] = $id;
                } else {
                    $child['id'] = $childExists['category_id'];
                }
            }
        }
        return $categories;
    }

    public function parseProducts($data, $productModel, $filterModel) {
        $url = isset($data['url']) ? $data['url'] : $this->siteUrl;
        $parser = new \viv\ProductsParser($url, $this->db);
        $parser->productModel = $productModel;
        $parser->filterModel = $filterModel;
        $result = $parser->parse($data);
        return $result;
    }
}