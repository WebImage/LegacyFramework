<?php

class DefaultProfileProvider extends ProfileProvider {
	public function createFromPageRequest($request_handler) {
		return new DefaultProfileProvider();
	}
}

?>