<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
require_once('CdataStrategy.php');

class CdataStrategyIllegalCharacters implements CdataStrategy
{
    private $illegalCharacters = array('<','>','&', '\'', '"','**LT**', '**GT**');

    public function should_cdata_be_applied($field, $hasSnippets = false)
    {
		if(empty($field)){
			return false;
		}

        if($hasSnippets) {
            $this->illegalCharacters = array('<','>','&', '**LT**', '**GT**');
        }
        
        foreach($this->illegalCharacters as $character) {
            if(strpos($field, $character) !== false) {
                return true;
            }
        }

        return false;
    }

}