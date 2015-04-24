<?php

namespace WebImage\ControlCompiler\Result;

interface IControlComponent {
	/**
	 * @return bool whether the component is initialized
	 **/
	public function isInitialized();
}