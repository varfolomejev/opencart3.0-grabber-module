<?php
class ControllerExtensionModuleVivparser extends Controller
{
	private $error = array();

	private $brandsKey = 'viv_brands';

	public function index()
	{
        $this->load->model('extension/module/vivparser/vivparser');
		$this->load->language('extension/module/vivparser');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/module');


		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('vivparser', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);
		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/vivparser', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/vivparser', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}
		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/vivparser', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/vivparser', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}
		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['module_description'])) {
			$data['module_description'] = $this->request->post['module_description'];
		} elseif (!empty($module_info)) {
			$data['module_description'] = $module_info['module_description'];
		} else {
			$data['module_description'] = array();
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '';
		}

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		// settings
//		$data['brands'] = $this->model_setting_setting->getSetting($this->brandsKey);
		$this->response->setOutput($this->load->view('extension/module/vivparser', $data));
	}

	public function brands() {
        $this->load->model('catalog/manufacturer');
        $this->load->model('extension/module/vivparser/vivparser');
        $this->load->model('setting/setting');
        $data = [];
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $data = $this->model_extension_module_vivparser_vivparser->parseBrands(
                $this->model_catalog_manufacturer
            );
        }
	    echo json_encode($data);
	    exit;
    }

    public function categories() {
        $this->load->model('catalog/category');
        $this->load->model('extension/module/vivparser/vivparser');
        $this->load->model('setting/setting');
        $data = [];
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $data = $this->model_extension_module_vivparser_vivparser->parseCategories(
                $this->model_catalog_category
            );
        }
        echo json_encode($data);
        exit;
    }

    public function products() {
        $this->load->language('catalog/filter');
        $this->load->model('catalog/product');
        $this->load->model('extension/module/vivparser/vivparser');
        $this->load->model('setting/setting');
        $data = [];
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $data = $this->model_extension_module_vivparser_vivparser->parseProducts(
                $this->request->post,
                $this->model_catalog_product,
                $this->model_catalog_filter
            );
        }
        echo json_encode($data);
        exit;
    }

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/vivparser')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		return !$this->error;
	}

	public function install()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('module_vivparser', ['module_vivparser_status' => 1]);
	}

	public function uninstall()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_vivparser');
	}
}
