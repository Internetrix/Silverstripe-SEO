<?php
/**
 * SEO_SitemapImageAutocompleter
 *
 * XML sitemap image autocompleter
 *
 * @package silverstripe-seo
 * @license MIT License https://github.com/cyber-duck/silverstripe-seo/blob/master/LICENSE
 * @author  <andrewm@cyber-duck.co.uk>
 **/
class SEO_SitemapImageAutocompleter extends GridFieldAddExistingAutocompleter
{	
    /**
     * Set core properties
     *
     * @since version 1.0.0
     *
     * @param string $targetFragment
     * @param object $searchFields
     *
     * @return void
     **/
	public function __construct($targetFragment = 'before', $searchFields = null)
	{
		$this->targetFragment = $targetFragment;
		$this->searchFields = (array)$searchFields;

		parent::__construct();
	}

    /**
     * Perform a search and return JSON encoded results for use in autocompleter
     *
     * @since version 1.0.0
     *
     * @param object $gridField
     * @param object $request
     *
     * @return void
     **/
	public function doSearch($gridField, $request)
	{
		$dataClass = $gridField->getList()->dataClass();
		$allList = $this->searchList ? $this->searchList : DataList::create($dataClass);
		
		$searchFields = ($this->getSearchFields())
			? $this->getSearchFields()
			: $this->scaffoldSearchFields($dataClass);
		if(!$searchFields) {
			throw new LogicException(
				sprintf('GridFieldAddExistingAutocompleter: No searchable fields could be found for class "%s"',
				$dataClass));
		}

		$params = array();
		foreach($searchFields as $searchField) {
			$name = (strpos($searchField, ':') !== FALSE) ? $searchField : "$searchField:StartsWith";
			$params[$name] = $request->getVar('gridfield_relationsearch');
		}
		$results = File::get()
			->filterAny($params)
			->filter('ClassName','Image')
			->sort(strtok($searchFields[0], ':'), 'ASC')
			->limit($this->getResultsLimit());

		$json = array();
		$originalSourceFileComments = Config::inst()->get('SSViewer', 'source_file_comments');
		Config::inst()->update('SSViewer', 'source_file_comments', false);
		foreach($results as $result) {
			$json[$result->ID] = html_entity_decode(SSViewer::fromString($this->resultsFormat)->process($result));
		}
		Config::inst()->update('SSViewer', 'source_file_comments', $originalSourceFileComments);

		return Convert::array2json($json);
	}
}