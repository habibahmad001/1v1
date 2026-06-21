<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
require_once('CdataStrategy.php');

class CdataStrategyIllegalCharactersHtmlEntities implements CdataStrategy
{
    public function should_cdata_be_applied($field, $hasSnippets = false)
    {
        return strlen($field) != strlen(htmlentities($field));
    }

}