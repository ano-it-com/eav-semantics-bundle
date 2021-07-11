<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Provider;

class SemanticBaseTerms
{

    public const TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
    public const OWL_CLASS = 'http://www.w3.org/2002/07/owl#Class';
    public const SUB_CLASS_OF = 'http://www.w3.org/2000/01/rdf-schema#subClassOf';
    public const OBJECT_PROPERTY = 'http://www.w3.org/2002/07/owl#ObjectProperty';
    public const DATATYPE_PROPERTY = 'http://www.w3.org/2002/07/owl#DatatypeProperty';
    public const SUB_PROPERTY_OF = 'http://www.w3.org/2000/01/rdf-schema#subPropertyOf';
    public const DOMAIN = 'http://www.w3.org/2000/01/rdf-schema#domain';
    public const RANGE = 'http://www.w3.org/2000/01/rdf-schema#range';
    public const SCHEMA_ORG_DOMAIN_INCLUDES = 'http://schema.org/domainIncludes';
    public const SCHEMA_ORG_RANGE_INCLUDES = 'http://schema.org/rangeIncludes';
    public const SCHEMA_ORG_DATA_TYPE = 'http://schema.org/DataType';
    public const RDFS_PROPERTY = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property';
    public const LABEL = 'http://www.w3.org/2000/01/rdf-schema#label';
    public const COMMENT = 'http://www.w3.org/2000/01/rdf-schema#comment';
}