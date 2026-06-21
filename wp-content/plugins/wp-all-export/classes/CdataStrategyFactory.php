<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
require_once(__DIR__.'/CdataStrategyAlways.php');
require_once(__DIR__.'/CdataStrategyIllegalCharacters.php');
require_once(__DIR__.'/CdataStrategyNever.php');


class CdataStrategyFactory
{
    public function create_strategy($strategy) {

        if($strategy == 'all') {
            return new CdataStrategyAlways();
        } else if($strategy == 'never') {
            return new CdataStrategyNever();
        } else if($strategy == 'auto') {
            return new CdataStrategyIllegalCharacters();
        } else {
            return new CdataStrategyIllegalCharacters();
        }
    }
}