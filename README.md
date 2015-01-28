DataTables Listing plugin
===
[![Codacy Badge](https://www.codacy.com/project/badge/6b4d2145ccb24f52a909290d33803aed)](https://www.codacy.com/public/pawellen/DataTablesListing)
[![Build Status](https://secure.travis-ci.org/pawellen/DataTablesListing.png?branch=master)](https://travis-ci.org/pawellen/DataTablesListing)

Data tables listing plugin allow you to easy creating record list, using Symfony forms style. This plugin use popular JQuery DataTables plug (http://www.datatables.net/)

Installation
===

+  Install plugin using composer:
```js
{
    "pawellen/data-tables-listing": "dev-master"
}
```

+  Update Your AppKernel.php
```php
$bundles = array (
    (...)
    new PawelLen\DataTablesListing\DataTablesListingBundle()
);
```

+  Configuration:

data_tables_listing:
    default_id_property: "id"
    default_template: LenPanelBundle::listing_div_layout.html.twig
    include_assets:
        datatables_js: false
        datatables_css: false
        include_jquery: false
        jquery_js: "//code.jquery.com/jquery-2.1.3.min.js"

***default_id_property*** - stands for root entity identifier property, currently used to set ***tr*** id attribute
***default_template***    - allow overwrite default template
***include_assets***      - asset files references (used in render_listing_assets twig function), if set to ***false*** asset wont be included
    ***datatables_js***       - reference to DataTables js source file
    ***datatables_css***      - reference to DataTables css source file
    ***include_jquery***      - decide if jquery should be included, default false
    ***jquery_js***           - reference to jQuery source file


+  Add assets to your template using ***render_listing_assets*** twig function.
Example:
```html
    <html>
        <head>
            (...)
            <script src="{{ asset('bundles/exapmlebundle/js/my_script.js') }}" type="text/javascript"></script>
            {{ render_listing_assets() }}
            (...)
        </head>
        <body>
        (...)
        </body>
    </html>
```

+  Your listing is ready to use :)

Usage
===

1. Creating new table in controller.
---
To create listing you just need to get ***listing*** service in your controller and pass to it your listing type object.
Example:

```php
    /**
     * @Route("/user/list")
     * @Template()
     */
    public function listAction(Request $request)
    {
        // Creates new listing object:
        $list = $this->get('listing')->createListing(new UserListing(), array(
            'request' => $request
        ));

        // Handle ajax request that provide data to listing:
        if ($request->isXmlHttpRequest()) {

            return $list->createResponse($request);
        }

        // Pass ListView object to your template:
        return array(
            'list' => $list->createView()
        );
    }
```

As you can see usage of listing is very similar to Symfony forms component.
 As one of options passed to ***createListing*** method is Request object. Request object is used to get any data passed form your site, for example filters, column order by itp...

2. Creating own ListingType
---

Creating new listing type looks similar like creation of symfony form.
Example:

```php
namespace Td\UserBundle\Listing;

use PawelLen\DataTablesListing\Type\AbstractType;
use PawelLen\DataTablesListing\Filters\FilterBuilderInterface;
use PawelLen\DataTablesListing\Listing\ListingBuilderInterface;

class UserListing extends AbstractType
{
    public function buildFilters(FilterBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array(
            'label' => 'User name',
            'required' => false,
            'filter' => array(
                'property' => 'name',
                'expression' => 'c.name LIKE :name'
            )
        ));
        $builder->add('email', 'text', array(
            'label' => 'Email',
            'required' => false,
            'filter' => array(
                'expression' => 'c.shortName LIKE :shortName'
            )
        ));
    }

    public function buildListing(ListingBuilderInterface $builder, array $options)
    {
        $builder->add('id', 'column', array(
            'label' => 'Id'
        ));
        $builder->add('name', 'column', array(
            'label' => 'User name'
        ));
        $builder->add('email', 'column', array(
            'label' => 'Email'
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'class' => 'TdUserBundle:User'
        ));
    }

    public function getName()
    {
        return 'my_list';
    }
}
```

This will create listing of users with two filter fields and three columns. ***Data source is pointed in setDefaultOptions*** method.
 Instead of single entity passed in "class" option you can use also option query_builder and provide query builder interface to access more complicated query with joins.

3. Template
---

To render created listing, you must pass ListingView object to your template:
Example:

```twig
{% extends "PanelBundle::base_template.html.twig" %}

{% form_theme list.filters _self %}

{% block _my_list_widget %}
    <div class="row">
        <div class="col-md-6">
            {{ form_row(form.name) }}
        </div>
        <div class="col-md-6">
            {{ form_row(form.email) }}
        </div>
    </div>
{% endblock _my_list_widget %}

{% block content_body %}
    {{ listing(list) }}
{% endblock %}

```

To render entire listing, you can use ***listing()*** twig function. This example shows also how to overwrite filters template. Notice that there is a leading underscore in block name.

Functions
===

1. Creating links
---

To create link you need to add ***link*** option inside ***buildListing*** method.

```php
    $builder->add('name', 'column', array(
        'label' => 'User name',
        'link' => array(
            'route' => 'user_edit',
            'params' => array(
                'user_id' => 'id'
            )
        )
    ));
```

where:
    ***id*** is a ***propertyPath*** string.
    ***route*** is a route name
    ***params*** are parameters to generateUrl function


2. Using QueryBuilder
---

To use query builder you need to use ***query_builder*** option instead of class.

```php
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'query_builder' => function(QueryBuilder $builder) {
                $builder->select('u, a, t')
                  ->from('TdUserBundle:Contractor', 'u')
                  ->leftJoin('u.address', 'a')
                  ->leftJoin('u.type', 't')
                  ->where('u.deletedAt IS NULL');
            }
        ));
    }
```

or

```php
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'class' => 'TdUserBundle:User',
            'query_builder' => function(EntityRepository $er) {
                return $er->createQueryBuilder('u')
                          ->where('u.deletedAt IS NULL');
            }
        ));
    }
```

3. Using filters
---

By default, when building filters form you don't need to define any filters, you may pass empty array as filter options, in this case
 default search will be performed. Default search use "name LIKE %PHRASE%" sql query, where ***name*** is filter name and ***PHRASE***
 is the value of input. To use custom filter just pass the DQL expression to filter option.
 Example:

```php
    $builder->add('name', 'text', array(
        'label' => 'User name',
        'required' => false,
        'filter' => array(
            'expression' => 'u.name LIKE :name',
            'eval' => '%like%'
        )
    ))
```

where:
    ***expression*** is a DQL expression, You can use parameter multiple times, for example: "u.firstname LIKE :name OR u.lastname LIKE :name".
    ***eval*** is not required parameter it is used to modify value passed from filter form

notice:
    ***:name*** MUST be always same as filter name (in current version)

4. Using query modifications when filter is used
---

In some cases your listing is very simple but there is case when you use some filters you have to add some complicated joins, but in other cases you don't want
 apply that joins to query because they are very expensive.
 Example:

 ```php
     $builder->add('languageCode', 'text', array(
         'label' => 'Language code',
         'required' => false,
         'filter' => array(
             'expression' => 'l.code LIKE :languageCode',
             'eval' => '%like%',
             'query_builder' => function(QueryBuilder $builder) {
                $builder->addSelect('l')
                        ->join('c.language', 'l');
             }
         )
     ))
```

In this example when user fill "Language code" filter, ***join('c.language')*** will be added and languageCode condition will be added to query.

***Deprecated*** parameter joins (used in old version):

```php
     // DEPRECATED:
     $builder->add('country', 'text', array(
         'label' => 'Country name',
         'required' => false,
         'filter' => array(
             'expression' => 'c.name LIKE :country',
             'eval' => '%like%',
             'join' => array(
                 array('field' => 'u.address', 'alias' => 'a'),
                 array('field' => 'a.country', 'alias' => 'c'),
             )
         )
     ))
```

In this case country will be joined only if country filter is passed by user, otherwise joins are not used.


5. Buttons
---

Inside ***buildListing*** method you can add action buttons to your list position.
Example:

```php
    ->add('edit', 'button', array(
        'label' => 'Edit',
        'route' => 'user_edit',
        'params' => array(
            'contractor_id' => 'id'
        )
    ))
```

6. Events
---
When you need dynamically modify/extend table rows or filters search criteria you can use one of listing events.


7. Accessing custom properties via PropertyAccessor
---

When using ***query_bulder*** you can use option ***property*** to access any data from fetched entity. For example:
If you have related entity you can can access its getter threw property accessor:

```php
    $builder->add('status', 'text', array(
        'label' => 'Status',
        'property' => 'status.name',
    ));
```

or you can even can access first object in collection:

```php
    $builder->add('user', 'text', array(
        'label' => 'First user',
        'property' => 'users[0].name',
    ));
```

listing allow you tu use some magic wildcard ***[*]***, to collect all values in collection and display it as coma
separated string:

```php
    $builder->add('user', 'text', array(
        'label' => 'Users',
        'property' => 'users[*].name',
    ));
```

***NOTE:*** You can use onlu one wildcard in your property option.
