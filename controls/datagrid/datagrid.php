<?php
/**
 * 01/14/2010	(Robert Jones) Added headerClass as an option to set a per column header css class (as opposed to just at the <Columns /> level
 * 01/18/2010	(Robert Jones) Added <NoResults> as an option to display something if there are not any results.
 * 01/27/2010	(Robert Jones) Modified class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 * 01/31/2010	(Robert Jones) Modified class to move <NoResults> inside of the <Columns /> tag so that there is a single XML root
 * 09/14/2011	(Robert Jones) Added $tableClass property/attribute so that a class can be applied to the actual table
 * 08/15/2012	(Robert Jones) Added <thead /> and <tbody /> to table output
 */
FrameworkManager::loadControl('datalist');
class DataGridControl extends DataListControl {
	/**
	 * <cms:DataGrid id="dg1" dataSource="ContentLogic::getAllContent()">
	 *	<Columns rowClass="standardRow,alternatingRow" headerClass="header">
	 *		<Column headerText="ID" field="id" />
	 *		<Column headerText="The Title" headerClass="css_class" class="field_class"><![CDATA[
	 *			<a href="checkout.php?id=<Data field="id" />"><Data field="title" /></a>
	 *		]]></Column>
	 *		<NoResults><![CDATA[]]></NoResults>
	 * 	</Columns>
	 * </cms:DataGrid>
	 */
	protected function init() {
		parent::init();
		$this->setInitParams(array(
			// Table attributes
			'width' => '',
			'border' => '',
			'cellSpacing' => '',
			'cellPadding' => '',
			'formatHeaderClass' => '',
			'formatRowClass' => array()
		));
	}
	
	public function getWidth() { return $this->getParam('width'); }
	public function getCellSpacing() { return $this->getParam('cellSpacing'); }
	public function getCellPadding() { return $this->getParam('cellPadding'); }
	public function getBorder() { return $this->getParam('border'); }
	public function getFormatHeaderClass() { return $this->getParam('formatHeaderClass'); }
	public function getFormatRowClass() {
		$row_class = $this->getParam('formatRowClass');
		if (empty($row_class)) $row_class = array();
		return $row_class;
	}

	public function setWidth($width) { $this->setParam('width', $width); }
	public function setCellSpacing($cell_spacing) { $this->setParam('cellSpacing', $cell_spacing); }
	public function setCellPadding($cell_padding) { $this->setParam('cellPadding', $cell_padding); }
	public function setBorder($border) { $this->setParam('border', $border); }
	public function setFormatHeaderClass($class) { return $this->setParam('formatHeaderClass', $class); }
	public function setFormatRowClass($class) { return $this->setParam('formatRowClass', $class); }

	/**
	 * @return bool
	 * @access protected?
	 * @throws Exception
	 */
	function prepareInternal() {
		
		try {
			$xml = CWI_XML_Compile::compile($this->getInnerCode());
		} catch (CWI_XML_CompileException $e) {
			return false;
		}

		if ($template = $xml->getPathSingle('/Columns')) {
			$table_attributes = '';

			$width		= $this->getWidth();
			$border		= $this->getBorder();
			$cell_spacing	= $this->getCellSpacing();
			$cell_padding	= $this->getCellPadding();
			$tableClass		= $this->getParam('tableClass');
			
			if (strlen($width) > 0) $table_attributes .= ' width="' . $width . '"';
			if (strlen($border) == 0) $border = 0;
			if (strlen($cell_spacing) == 0) $cell_spacing = 0;
			if (strlen($cell_padding) == 0) $cell_padding = 0;
			if (strlen($tableClass) > 0) $table_attributes .= ' class="' . $tableClass . '"';
			
			$header_template = '<table cellpadding="' . $cell_padding . '" cellspacing="' . $cell_spacing . '" border="' . $border . '"' . $table_attributes . '>';
			$footer_template = '</table>';

			// Table Formating
			if ($template->getParam('rowClass')) $this->setFormatRowClass( explode(',', $template->getParam('rowClass')) );
			if ($template->getParam('headerClass')) $this->setFormatHeaderClass( $template->getParam('headerClass') );
			
			$header_class = $this->getFormatHeaderClass();

			$item_template = '';
			
			// Define formats
			if ($columns = $template->getPath('Column')) {
			
				$using_headers = false;
				$headers = array();
				foreach($columns as $column) {
					if ($header_text = $column->getParam('headerText')) {
						$using_headers = true;
					}
					if ($column_header_class = $column->getParam('headerClass')) {
						$column_header_class = ' class="' . $column_header_class . '"';
					} else {
						$column_header_class = $header_class;
					}
					
					$headers[] = '<th' . $column_header_class . '>'. $header_text . '</th>';
					#$headers[] = '<th>'. $header_text . '</th>';

					
					$cell_attributes = '';
					if ($column->getParam('width')) $cell_attributes .= ' width="' . $column->getParam('width') . '"';
					if ($column->getParam('height')) $cell_attributes .= ' height="' . $column->getParam('height') . '"';
					if ($column->getParam('align')) $cell_attributes .= ' align="' . $column->getParam('align') . '"';
					if ($column->getParam('vAlign')) $cell_attributes .= ' valign="' . $column->getParam('valign') . '"';
					if ($column->getParam('class')) $cell_attributes .= ' class="' . $column->getParam('class') . '"';
					
					if ($column->getParam('field')) {
						$format = '';
						if ($column->getParam('format')) $format = ' format="' . $column->getParam('format') . '"';
						$column_template = '<td' . $cell_attributes . '><Data field="' . $column->getParam('field') . '" ' . $format . '/></td>';
					} else {
						$column_template = '<td' . $cell_attributes . '>' . $column->getData() . '</td>';
					}
					
					$item_template .= $column_template;
					
				}
			}

			$row_classes = $this->getFormatRowClass();
			if (count($row_classes) > 0) {
				foreach($row_classes as $row_class) {
					$this->addItemTemplateByHtml('<tr class="' . $row_class . '">' . $item_template . '</tr>');
				}
			} else {
				$this->addItemTemplateByHtml('<tr>' . $item_template . '</tr>');
			}
			
			
			if ($using_headers) {
				$header_template .= '<thead>';
				$header_template .= '<tr>';
				$header_template .= implode('', $headers);
				$header_template .= '</tr>';
				$header_template .= '</thead>';
			}
			
			// Wrap table columns in <tbody />
			$header_template .= '<tbody>';
			$footer_template = '</tbody>' . $footer_template;
			
			$this->setHeaderTemplate($header_template);
			
			$this->setFooterTemplate($footer_template);
			
			if ($no_results = $template->getPathSingle('NoResults')) {
				$this->setEmptyTemplate($no_results->getData());
			}
		}
		return true;
	}
	
}