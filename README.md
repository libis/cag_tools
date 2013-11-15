ca_cag_tools
============

Collective access scripts and installation profile for CAG

delete from ca_attribute_values where attribute_id > 2251;
delete from ca_attributes where attribute_id > 2251;
delete from ca_entity_labels where locale_id = 1;
delete from ca_entities where locale_id = 1;