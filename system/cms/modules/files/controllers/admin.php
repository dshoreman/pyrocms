<?php 

use Pyro\Module\Files\Model\File;
use Pyro\Module\Files\Model\Folder;

/**
 * PyroCMS file Admin Controller
 *
 * Provides an admin for the file module.
 *
 * @author		Jerel Unruh - PyroCMS Dev Team
 * @package		PyroCMS\Core\Modules\Files\Controllers
 */
class Admin extends Admin_Controller {

	private $_folders	= array();
	private $_type 		= null;
	private $_ext 		= null;
	private $_filename	= null;

	// ------------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();

		$this->config->load('files');
		$this->lang->load('files');
		$this->load->library('files/files');

		$allowed_extensions = '';

		foreach (config_item('files:allowed_file_ext') as $type) 
		{
			$allowed_extensions .= implode('|', $type).'|';
		}

		$this->template->append_metadata(
			"<script>
				pyro.lang.fetching = '".lang('files:fetching')."';
				pyro.lang.fetch_completed = '".lang('files:fetch_completed')."';
				pyro.lang.start = '".lang('files:start')."';
				pyro.lang.width = '".lang('files:width')."';
				pyro.lang.height = '".lang('files:height')."';
				pyro.lang.ratio = '".lang('files:ratio')."';
				pyro.lang.full_size = '".lang('files:full_size')."';
				pyro.lang.cancel = '".lang('buttons:cancel')."';
				pyro.lang.synchronization_started = '".lang('files:synchronization_started')."';
				pyro.lang.untitled_folder = '".lang('files:untitled_folder')."';
				pyro.lang.exceeds_server_setting = '".lang('files:exceeds_server_setting')."';
				pyro.lang.exceeds_allowed = '".lang('files:exceeds_allowed')."';
				pyro.files = { permissions : ".json_encode(Files::allowedActions())." };
				pyro.files.max_size_possible = '".Files::$max_size_possible."';
				pyro.files.max_size_allowed = '".Files::$max_size_allowed."';
				pyro.files.valid_extensions = '/".trim($allowed_extensions, '|')."$/i';
				pyro.lang.file_type_not_allowed = '".addslashes(lang('files:file_type_not_allowed'))."';
				pyro.lang.new_folder_name = '".addslashes(lang('files:new_folder_name'))."';
				pyro.lang.alt_attribute = '".addslashes(lang('files:alt_attribute'))."';

				// deprecated
				pyro.files.initial_folder_contents = ".(int)$this->session->flashdata('initial_folder_contents').";
			</script>");
	}

	/**
	 * Folder Listing
	 */
	public function index()
	{
		$this->template
			->title($this->module_details['name'])
			->append_css('jquery/jquery.tagsinput.css')
			->append_css('module::jquery.fileupload-ui.css')
			->append_css('module::files.css')
			->append_js('jquery/jquery.tagsinput.js')
			->append_js('module::jquery.fileupload.js')
			->append_js('module::jquery.fileupload-ui.js')
			->append_js('module::functions.js')
			// should we show the "no data" message to them?
			->set('folders', Folder::findByParent(0)->count())
			->set('locations', array_combine(Files::$providers, Files::$providers))
			->set('folder_tree', Files::folderTree());

		$path_check = Files::checkDir(Files::$path);

		if ( ! $path_check['status']) {
			$this->template->set('messages', array('error' => $path_check['message']));
		}

		$this->template->build('admin/index');
	}

	/**
	 * Create a new folder
	 *
	 * Grabs the parent id and the name of the folder from POST data.
	 */
	public function new_folder()
	{
		// This is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('create_folder', Files::allowedActions())) {
			show_error(lang('files:no_permissions'));
		}

		$parent_id = $this->input->post('parent');
		$name = $this->input->post('name');

		$result = Files::createFolder($parent_id, $name);

		$result['status'] AND Events::trigger('file_folder_created', $result['data']);

		echo json_encode($result);
	}

	/**
	 * Set the initial folder ID to load contents for
	 *
	 * @deprecated
	 * 
	 * Accepts the parent id and sets it as flash data
	 */
	public function initial_folder_contents($id)
	{
		$this->session->set_flashdata('initial_folder_contents', $id);

		redirect(site_url('admin/files'));
	}

	/**
	 * Get all files and folders within a folder
	 *
	 * Grabs the parent id from the POST data.
	 */
	public function folder_contents()
	{
		$parent = $this->input->post('parent');

		echo json_encode(Files::folderContents($parent));
	}

	/**
	 * See if a container exists with that name.
	 *
	 * This is different than folder_exists() as this checks for unindexed containers.
	 * Grabs the name of the container and the location from the POST data.
	 */
	public function check_container()
	{
		$name = $this->input->post('name');
		$location = $this->input->post('location');

		echo json_encode(Files::checkContainer($name, $location));
	}

	/**
	 * Set the order of files and folders
	 */
	public function order()
	{
		if ($collection = $this->input->post('order')) {
			foreach ($collection as $type => $item) {
				$i = 0;

				foreach ($item as $id) {
					if ($type == 'folder') {
						$folder = Folder::find($id);
						$folder->sort = $i;
						$folder->save();
					} else {
						$file = File::find($id);
						$file->sort = $i;
						$file->save();
					}

					$i++;
				}
			}

			// let the files library format the return array like all the others
			echo json_encode(Files::result(true, lang('files:sort_saved')));
		} else {
			echo json_encode(Files::result(false, lang('files:save_failed')));
		}
	}

	/**
	 * Rename a folder
	 */
	public function rename_folder()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('edit_folder', Files::allowedActions())) {
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('folder_id') and $name = $this->input->post('name')) {
			$result = Files::renameFolder($id, $name);
			
			$result['status'] AND Events::trigger('file_folder_updated', $id);

			echo json_encode($result);
		}
	}

	/**
	 * Delete an empty folder
	 */
	public function delete_folder()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('delete_folder', Files::allowedActions())) {
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('folder_id')) {
			$result = Files::deleteFolder($id);

			$result['status'] AND Events::trigger('file_folder_deleted', $id);

			echo json_encode($result);
		}
	}

	/**
	 * Upload files
	 */
	public function upload()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('upload', Files::allowedActions()) AND
			// replacing files needs upload and delete permission
			! ( $this->input->post('replace_id') && ! in_array('delete', Files::allowedActions()) )
		) {
			show_error(lang('files:no_permissions'));
		}

		$result = null;
		$input = $this->input->post();

		if($input['replace_id'] > 0) {
			$result = Files::replaceFile($input['replace_id'], $input['folder_id'], $input['name'], 'file', $input['width'], $input['height'], $input['ratio'], $input['alt_attribute']);
			$result['status'] AND Events::trigger('file_replaced', $result['data']);
		} elseif ($input['folder_id'] and $input['name']) {
			$result = Files::upload($input['folder_id'], $input['name'], 'file', $input['width'], $input['height'], $input['ratio'], null, $input['alt_attribute']);
			$result['status'] AND Events::trigger('file_uploaded', $result['data']);
		}
		
		if($result==null) {
			$result = array('status' 	=> false, 
					 'message' 	=> "Unexpected error", 
					 );
		}

		echo json_encode($result);		
	}

	/**
	 * Rename a file
	 */
	public function rename_file()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('edit_file', Files::allowedActions())) {
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('file_id') and $name = $this->input->post('name')) {
			$result = Files::renameFile($id, $name);

			$result['status'] AND Events::trigger('file_updated', $result['data']);

			echo json_encode($result);
		}
	}

	/**
	 * Edit description of a file
	 */
	public function save_description()
	{
		$description 	= $this->input->post('description');
		$keywords_hash	= Keywords::process($this->input->post('keywords'), $this->input->post('old_hash'));
		$alt_attribute	= $this->input->post('alt_attribute');

		if ($id = $this->input->post('file_id')) {
			$file = File::find($id);
			$file->description = $description;
			$file->keywords = $keywords_hash;
			$file->alt_attribute = $alt_attribute;
			$file->save();

			echo json_encode(Files::result(true, lang('files:description_saved')));
		}
	}
		
	/**
	 * Edit the "alt" attribute of an image file
	 */
	public function save_alt()
	{
		if ($id = $this->input->post('file_id') AND $alt_attribute = $this->input->post('alt_attribute')) {
			$file = File::find($id);
			$file->alt_attribute = $alt_attribute;
			$file->save();
			
			echo json_encode(Files::result(TRUE, lang('files:alt_saved')));
		}
	}	 	

	/**
	 * Edit location of a folder (S3/Cloud Files/Local)
	 */
	public function save_location()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('set_location', Files::allowedActions())) {
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('folder_id') and $location = $this->input->post('location') and $container = $this->input->post('container')) {
			$folder = Folder::find($id);
			$folder->location = $location;
			$folder->save();

			echo json_encode(Files::createContainer($container, $location, $id));
		}
	}

	/**
	 * Pull new files down from the cloud location
	 */
	public function synchronize()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('synchronize', Files::allowedActions())) {
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('folder_id')) {
			echo json_encode(Files::synchronize($id));
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete a file
	 *
	 * @return	void
	 */
	public function delete_file()
	{
		// this is just a safeguard if they circumvent the JS permissions
		if ( ! in_array('delete_file', Files::allowedActions())) {
			show_error(lang('files:no_permissions'));
		}

		if ($id = $this->input->post('file_id')) {
			$result = Files::deleteFile($id);

			$result['status'] AND Events::trigger('file_deleted', $id);

			echo json_encode($result);
		}
	}

	/**
	 * Search for files and folders
	 */
	public function search()
	{
		echo json_encode(Files::search($this->input->post('search')));
	}

}
