<?php

namespace WebImage\ControlCompiler;

use Collection;
use Dictionary;
use ControlConfigDictionary;

/**
 * An object to hold the various results associated with a compiled control
 **/
class Result {
	private $inits, $attachs;
	private $params;//ControlConfigDictionary
	/**
	 * Keep track of parent pools and their current maximum sort order so that an "order" value can be
	 **/
	private $childOrder;
	function __construct() {

		// Instantiate properties
		$this->inits = new Collection(); // Collection instead of dictionary because the control name could change in code somewhere and the key value would have no way of updating
		$this->attachs = new Dictionary();
		$this->params = new ControlConfigDictionary();
		$this->parentChildOrder = new Dictionary();
	}
	public function addAutoLoadControlFile($file) {
		if (!$auto_load_control_files = $this->getParams()->get('auto_load_control_files')) {
			$auto_load_control_files = new Collection();
			$this->getParams()->set('auto_load_control_files', $auto_load_control_files);
		}
		$auto_load_control_files->add($file);
	}
	public function addAutoLoadTemplate($template_id) {
		if (!$auto_load_template_ids = $this->getParams()->get('auto_load_template_ids')) {
			$auto_load_template_ids = new Collection();
			$this->getParams()->set('auto_load_template_ids', $auto_load_template_ids);
		}
		$auto_load_template_ids->add($template_id);
	}
	/**
	 * Adds a set of values that will be used to initialize a class
	 * @param CompileControlInit An object holding the requisite initialization values (control class name, instance name, parameters)
	 **/
	public function addInitialization(Result\ControlInitializer $init) {
		$this->inits->add($init);
	}
	public function createInitialization($control_name, $instance_name, ControlConfigDictionary $params, $parent_name='') {

		$init = new Result\ControlInitializer($control_name, $instance_name, $params);
		$this->addInitialization($init);

		if (empty($parent_name)) {
			$this->generateControlOrder('__ROOT__', $instance_name);
		} else {
			$this->createAttachment($instance_name, $parent_name);
		}
		return $init;
	}
	private function getInitByName($n) {
		$inits = $this->getInitializations();
		while ($init = $inits->getNext()) {
			if ($init->getInstanceName() == $n) {
				$inits->resetIndex(); // Return index to proper state
				return $init;
			}
		}
	}
	/**
	 * Based on the passed $parent_name, find the next sort order for an added child
	 **/
	private function generateControlOrder($parent_name, $child_name) {

		// Find the init that the child belongs to...
		if ($init = $this->getInitByName($child_name)) {

			/**
			 * Check if it already has an "order" parameter attached to it, otherwise create one here
			 **/
			$param_order = $init->getParams()->get('order');

			if (empty($param_order)) {
				/**
				 * Current current max sort order
				 **/
				if (!$max_order = $this->parentChildOrder->get($parent_name)) {
					$max_order = 0;
				}
				$max_order ++;
				// Update param sort order
				$init->getParams()->set('order', $max_order);
				// Store new max order
				$this->parentChildOrder->set($parent_name, $max_order);

			}

		} else {
			$d = new Dictionary();
			$d->set('parent_name', $parent_name);
			$d->set('child_name', $child_name);
			Custodian::log('CompileControl', 'generateControlOrder(${parent_name}, ${child_name}) unable to find child control', $d, CUSTODIAN_WARNING, __FILE__.':'.__LINE__);
		}
	}
	/**
	 * Sets up a relationship between what child should be attached to what parent
	 **/
	public function addAttachment(Result\ControlAttachment $attachment) {
		$this->attachs->set($attachment->getChildName(), $attachment);
		// Generate next sort order if one is not defined for the child control (based on the parent control's current count)
		#echo 'addAttachment: ' . $attachment->getChildName() . ' to ' . $parent_name . '<br />';
		$this->generateControlOrder($attachment->getParentName(), $attachment->getChildName());
	}
	public function createAttachment($child_instance_name, $parent_instance_name) {
		if (empty($child_instance_name)) throw new Exception('$child_instance_name cannot be empty');
		if (empty($parent_instance_name)) throw new Exception('$parent_instance_name cannot be empty');

		$attachment = new Result\ControlAttachment($child_instance_name, $parent_instance_name);
		$this->addAttachment($attachment);
		return $attachment;
	}
	/**
	public function addRender($render) {
	}
	 **/
	/**
	 * Get the required initializations
	 * @return Collection
	 **/
	public function getInitializations() { return $this->inits; }
	/**
	 * Get the required attachments
	 * @return Dictionary
	 **/
	public function getAttachments() { return $this->attachs; }
	/**
	 * Get the parent id of an object (by id)
	 **/
	public function getParentId($object_id) {
		return $this->attachs->get($object_id);
	}
	/**
	 * Merge another compiled result set into this class
	 **/
	public function merge(Result $result) {

		$this->getInitializations()->merge($result->getInitializations());
		$this->getAttachments()->mergeDictionary($result->getAttachments());
		$this->params->mergeDictionary($result->getParams());

	}

	public function getParam($name) { return $this->params->get($name); }
	public function getParams() { return $this->params; }
	public function setParam($name, $value) { $this->params->set($name, $value); }
}