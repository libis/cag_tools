<?php
# CREATE or UPDATE or RELATION
define("__TYPE__", "RELATION");
# blank of ST of TEST
define("__PART__", "ST");

# 1.2. adlibObjectNummer     -> singlefield: adlibObjectNummer,
# 1.3. CAG objectnaam        -> container: objectNaam,
# 1.4. Alternatieve benaming -> singlefieldarray: Alternatief,
# 1.5. Alternatieve titel    -> singlefield: titelAlternatief,
# 2.1. Fysieke beschrijving  -> singlefield: physicalDescription,
# 2.2. Aantal objecten       -> singlefieldarray: numberOfObjects
# 2.5. Opschrift             -> container: opschrift
# 2.7. Materiaal             -> container: materiaal
# 2.8. Afmetingen            -> container: afmeting
# 2.9. Volledigheid          -> container: completeness
# 2.10. Toestand             -> container: toestand
# 3.1. Inh. beschrijving     -> singlefield: inhoud
# 4.5. Verwerving            -> container: acquisition
#                   voor cag_objecten_relaties.php
# 1. documentatie            -> occurrences (+container: regPaginaInfo)
# 2. vervaardiger            -> entities
# 3. vervaardiging           -> vervaardiging: (update object) -> ca_places
# 4. trefwoorden             -> vocabulary terms
# 5. collecties              -> collecties
# 6. bewaarinstelling        -> entities
# 7. inventarisnr            -> update object
# 8. verworven               -> entities
# 9. related                 -> objects (+update object)
#

# xml-file: objecten.xml of sinttruiden.xml
if (__PART__ == "") {
    define("__DATA__", __MY_DIR__."/cag_tools/data/objecten.xml");
} elseif (__PART__ == "ST") {
    define("__DATA__", __MY_DIR__."/cag_tools/data/sinttruiden.xml");
}elseif (__PART__ == "TEST"){
    define("__DATA__", __MY_DIR__."/cag_tools/data/test_objecten.xml");
}

# csv-file
if (__TYPE__ == "RELATION") {
    define("__MAPPING__", "cag_objecten_relaties_mapping.csv");
}else{
    define("__MAPPING__", "cag_objecten_mapping.csv");
}
