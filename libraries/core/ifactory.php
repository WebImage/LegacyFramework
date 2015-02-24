<?php

interface IFactory {
	public function createService(IServiceManager $sm);
}