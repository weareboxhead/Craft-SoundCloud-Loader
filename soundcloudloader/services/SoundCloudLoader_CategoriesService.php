<?php

namespace Craft;

class SoundCloudLoader_CategoriesService extends BaseApplicationComponent
{
	// Any multilple word tags will be split by this
	private $quote;
	private $quoteLength;
	private $marker;
	private $markerLength;

	function __construct()
	{
		$this->quote = '"';
		$this->quoteLength = strlen($this->quote); 
		$this->marker = ' - ';
		$this->markerLength = strlen($this->marker); 
	}

	private function existingCategorysId($categoryGroupId, $category)
	{
		// Create a Craft Element Criteria Model
		$criteria = craft()->elements->getCriteria(ElementType::Category);
		// Restrict the parameters to the correct category group
		$criteria->groupId = $categoryGroupId;

		foreach ($criteria as $existingCategory) {
			// If this category was found, return its id
			if ($existingCategory->slug === StringHelper::toKebabCase($category)) {
				return $existingCategory->id;
			}
		}

		// If none were found, return false
		return false;
	}

	private function saveSpecialCategory($group, $category)
	{
		// Get the handle for this group
		$groupHandle 		= craft()->soundCloudLoader_string->camelCase($group);
		// Get the category group
		$categoryGroup 		= craft()->categories->getGroupByHandle($groupHandle);
		// Get the category group id
		$categoryGroupId 	= $categoryGroup->id;

		// Remove any quotes from the category
		if (substr($category, 0, 1) === $this->quote) {
			$category = substr($category, 1, strlen($category) - 2);
		}
		
		// Remove the marker (Remove the length of the marker plus the marker)
		$category = substr($category, strlen($group) + $this->markerLength);

		// Get the id of this category
		$id = $this->existingCategorysId($categoryGroupId, $category);

		// If this category doesn't currently exist (no id was found), create it
		if (!$id) {
			// Create a new category model
			$newCategory = new CategoryModel();
			// Set the group id
			$newCategory->groupId = $categoryGroupId;
			// Set the title
			$newCategory->getContent()->title = $category;
			// Save the category
			craft()->categories->saveCategory($newCategory);
			// Get this id
			$id = $newCategory->id;
		}

		return $id;
	}

	private function separateSpecialCategories($data, $standardCategories, $categoryGroups)
	{

		// Set up an empty array
		$categoriesArray = array();

		foreach ($categoryGroups as $group) {
			// Set up an empty array for our category ids
			$ids = array();

			while (($startingIndex = strpos($standardCategories, strtolower($group) . $this->marker)) !== false)
			{
				// If this category has a quote before it, look for the next quote
				if (substr($standardCategories, $startingIndex - 1, 1) === $this->quote) {
					// The ending index is the next quote, plus the starting index, plus 1 to include the quote
					$endingIndex = strpos(substr($standardCategories, $startingIndex), $this->quote) + $startingIndex + 1;
					// Reduce the starting index by 1 to include the quote
					$startingIndex --;
				}
				// If this category doesn't have a quote before it
				else {
					// Find the next space
					$endingIndex = strpos(substr($standardCategories, $startingIndex), ' ');

					// If there wasn't one found, the end will be the end of the categories string
					if (!$endingIndex) {
						$endingIndex = strlen($standardCategories);
					}
				}

				// Get this special category
				$specialCategory = substr($standardCategories, $startingIndex, $endingIndex - $startingIndex);
				// Remove it from the 'standard' category list and trim any now existing white space
				$standardCategories = trim(substr($standardCategories, 0, $startingIndex) . substr($standardCategories, $endingIndex));
				// Get this category's id
				$ids[] = $this->saveSpecialCategory($group, $specialCategory);
			}

			// Create a key for the entry which matches this category's group
			$categoriesArray['soundCloudCategories' . ucfirst($group)] = $ids;
		}

		// Assign what is left of the category string as the standard categories
		$categoriesArray['standardCategories'] = $standardCategories;

		return $categoriesArray;
	}

	public function getCategories($data, $categoryGroups)
	{
		$categories 		= array();
		$standardCategories = '';
		$genreList 			= $data['genre'];
		$tagList 			= $data['tag_list'];

		// If this entry has tags (first one is counted as the genre)
		if (!empty($genreList) || !empty($tagList))
		{
			// If the genre is set
			if (!empty($genreList))
			{
				// Add it to the list
				$standardCategories .= trim($genreList); 

				// If the genre has a space inside of it, we need to quote it so it's formatting is consistent with the other tags
				if (strpos($standardCategories, ' '))
				{
					$standardCategories = $this->quote . $standardCategories . $this->quote;
				}
			}

			// If there is a remaining tag list
			if (!empty($tagList))
			{
				// Add in the rest of the tags
				$standardCategories .= ' ' . $tagList; 
			}

			// Lower case the tags to help limit the effects of human error on tag input
			$standardCategories = strtolower($standardCategories); 

			// If we need to check for special categories present, handle that separately
			if (is_array($categoryGroups)) {
				$categories = $this->separateSpecialCategories($data, $standardCategories, $categoryGroups);
			}
			// Otherwise, just assign the standard categories as a key here
			else {
				$categories['standardCategories'] = $standardCategories;
			}
		}

		return $categories;
	}
}