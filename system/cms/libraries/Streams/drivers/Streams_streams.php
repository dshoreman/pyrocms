<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Entries Driver
 * 
 * @author  	Parse19
 * @package  	PyroCMS\Core\Libraries\Streams\Drivers
 */
class Streams_streams extends CI_Driver {

	/**
	 * Get entries for a stream.
	 *
	 * @param	string    stream name
	 * @param	string    stream slug
	 * @param	string    stream namespace
	 * @param	string    stream prefix
	 * @param	string    about notes for stream
	 * @param 	array     extra data
	 * @return	bool
	 */
	public function add_stream($stream_name, $stream_slug, $namespace, $prefix = null, $about = null, $extra = array())
	{
		// -------------------------------------
		// Validate Data
		// -------------------------------------
		
		// Do we have a stream name?
		if ( ! trim($stream_name)) {
			$this->log_error('empty_stream_name', 'add_stream');
			return false;
		}				

		// Do we have a stream slug?
		if ( ! trim($stream_slug)) {
			$this->log_error('empty_stream_slug', 'add_stream');
			return false;
		}				

		// Do we have a stream namespace?
		if ( ! trim($namespace)) {
			$this->log_error('empty_stream_namespace', 'add_stream');
			return false;
		}				
		
		// Is this stream slug already available?
		if (is_object(ci()->streams_m->get_stream($stream_slug, true)) ) {
			$this->log_error('stream_slug_in_use', 'add_stream');
			return false;
		}
	
		// -------------------------------------
		// Create Stream
		// -------------------------------------
		
		return ci()->streams_m->create_new_stream(
			$stream_name,
			$stream_slug,
			$prefix,
			$namespace,
			$about,
			$extra
		);
	}

	// --------------------------------------------------------------------------

	/**
	 * Get Stream
	 *
	 * @param	mixed $stream object, int or string stream
	 * @param	string $namespace namespace if first param is string
	 * @return	object
	 */
	public function get_stream($stream, $namespace = null)
	{
		$str_id = $this->stream_id($stream, $namespace);
		
		if ( ! $str_id) $this->log_error('invalid_stream', 'get_stream');

		return ci()->streams_m->get_stream($str_id);
	}

	// --------------------------------------------------------------------------

	/**
	 * Delete a stream
	 *
	 * @param	mixed $stream object, int or string stream
	 * @param	string $namespace namespace if first param is string
	 * @return	object
	 */
	public function delete_stream($stream, $namespace = null)
	{
		$str_obj = $this->stream_obj($stream, $namespace);
		
		if ( ! $str_obj) $this->log_error('invalid_stream', 'delete_stream');
	
		return ci()->streams_m->delete_stream($str_obj);
	}

	/**
	 * Update a stream
	 *
	 * @param	mixed $stream object, int or string stream
	 * @param	string $namespace namespace if first param is string
	 * @param 	array $data associative array of new data
	 * @return	object
	 */
	public function update_stream($stream, $namespace = null, $data = array())
	{	
		$str_id = $this->stream_id($stream, $namespace);
		
		if ( ! $str_id) $this->log_error('invalid_stream', 'update_stream');
		
		$data['stream_slug'] = $stream;

		return ci()->streams_m->update_stream($str_id, $data);
	}

	/**
	 * Get stream field assignments
	 *
	 * @param	mixed $stream object, int or string stream
	 * @param	string $namespace namespace if first param is string
	 * @return	object
	 */
	public function get_assignments($stream, $namespace = null)
	{
		$str_id = $this->stream_id($stream, $namespace);
		
		if ( ! $str_id) $this->log_error('invalid_stream', 'get_stream');

		return ci()->fields_m->get_assignments_for_stream($str_id);
	}

	/**
	 * Get streams in a namespace
	 *
	 * @param	string $namespace namespace
	 * @param 	int [$limit] limit, defaults to null
	 * @param 	int [$offset] offset, defaults to 0
	 * @return	object
	 */
	public function get_streams($namespace, $limit = null, $offset = 0)
	{
		return ci()->streams_m->get_streams($namespace, $limit, $offset);
	}

	/**
	 * Get Stream Metadata
	 *
	 * Returns an array of the following data:
	 *
	 * name 			The stream name
	 * slug 			The streams slug
	 * namespace 		The stream namespace
	 * db_table 		The name of the stream database table
	 * raw_size 		Raw size of the stream database table
	 * size 			Formatted size of the stream database table
	 * entries_count	Number of the entries in the stream
	 * fields_count 	Number of fields assigned to the stream
	 * last_updated		Unix timestamp of when the stream was last updated
	 *
	 * @param	mixed $stream object, int or string stream
	 * @param	string $namespace namespace if first param is string
	 * @return	object
	 */
	public function get_stream_metadata($stream, $namespace = null)
	{
		$stream = $this->get_stream($stream, $namespace);

		$data = array();

		$data['name']		= $stream->stream_name;
		$data['slug']		= $stream->stream_slug;
		$data['namespace']	= $stream->stream_namespace;

		// Get DB table name
		$data['db_table'] 	= $stream->stream_prefix.$stream->stream_slug;

		// Get the table data
		$info = ci()->pdb->query("SHOW TABLE STATUS LIKE '".ci()->pdb->dbprefix($data['db_table'])."'")->row();
		
		// Get the size of the table
		$data['raw_size']	= $info->Data_length;

		ci()->load->helper('number');
		$data['size'] 		= byte_format($info->Data_length);
		
		// Last updated time
		$data['last_updated'] = ( ! $info->Update_time) ? $info->Create_time : $info->Update_time;

		ci()->load->helper('date');
		$data['last_updated'] = mysql_to_unix($data['last_updated']);
		
		// Get the number of rows (the table status data on this can't be trusted)
		$data['entries_count'] = ci()->db->count_all($data['db_table']);
		
		// Get the number of fields
		$data['fields_count'] = ci()->db->select('id')->where('stream_id', $stream->id)->get(ASSIGN_TABLE)->num_rows();

		return $data;
	}
	
	// --------------------------------------------------------------------------

	/**
	 * Chekc is table exists
	 * 
	 * Check to see if the table name needed for a stream is
	 * actually available.
	 *
	 * @param 	string
	 * @param 	string
	 * @param 	string
	 */
	public function check_table_exists($stream_slug, $prefix)
	{
		return ci()->streams_m->check_table_exists($stream_slug, $prefix);
	}

	// --------------------------------------------------------------------------

	/**
	 * Validation Array
	 *
	 * Get a validation array for a stream. Takes
	 * the format of an array of arrays like this:
	 *
	 * array(
	 * 'field' => 'email',
	 * 'label' => 'Email',
	 * 'rules' => 'required|valid_email'
	 */
	public function validation_array($stream, $namespace = null, $method = 'new', $skips = array(), $row_id = null)
	{
		$str_id = $this->stream_id($stream, $namespace);
		
		if ( ! $str_id) $this->log_error('invalid_stream', 'validation_array');

		$stream_fields = ci()->streams_m->get_stream_fields($str_id);

		return ci()->fields->set_rules($stream_fields, $method, $skips, true, $row_id);
	}	
}