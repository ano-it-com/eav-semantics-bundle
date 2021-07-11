# EAV Semantics

Библиотека по работе с семантическими значениями для EAV-bundle

## Основные возможности
* Работа с базовыми понятиями онтологий
* Работы с онтологиями и триплетами онтологий

## Установка

```
// установка миграций  
php bin/console eav-semantics:install  

// установка базовых онтологий    
php bin/console eav-semantics:ontology:import  
```

## Провайдеры данных

### SemanticProvider

Семантический провайдер. Реализует работу с базовыми понятиями онтологий:

`hasClass` - наличие класса в онтологии    
`subClasses` - дочерние классы класса   
`superClasses` - родительские классы класса   
`hasDatatypeProperties` - наличие литеральных свойств   
`hasObjectProperties` - наличие объектных свойств   
`getDatatypeProperties` - список литеральных свойств   
`getObjectProperties` - список объектных свойств   
`subProperties` - дочерние свойства свойства   
`superProperties` - родительские свойства свойства 

### OntologyProvider

Провайдер онтологий. Реализует поиск по идентификатору или IRI онтологии

### OntologyTripletsProvider

Провайдер фактов (триплетов) из онтологии. Реализует поиск по фильтрам.

```
$criteria = (new TripletCriteria())
    ->where(TripletCriteria::SUBJECT, '=', 'http://test.iri/someClass')
    ->where(TripletCriteria::PREDICATE, '=', SemanticBaseTerms::TYPE)
    ->where(TripletCriteria::OBJECT, '=', SemanticBaseTerms::OWL_CLASS);

$triple = $tripletsProvider->findOneBy([ $criteria ]);
```

### OntologyPersister

Реализует сохранение онтологий и триплетов онтологий в базу данных
