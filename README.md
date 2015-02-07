DataTables Listing plugin
===
[![Codacy Badge](https://www.codacy.com/project/badge/6b4d2145ccb24f52a909290d33803aed)](https://www.codacy.com/public/pawellen/DataTablesListing)
[![Build Status](https://secure.travis-ci.org/pawellen/DataTablesListing.png?branch=master)](https://travis-ci.org/pawellen/DataTablesListing)

Data tables listing plugin allow you to easy creating record list, using Symfony forms style. This plugin use popular JQuery DataTables plug (http://www.datatables.net/)

Installation
===

1. Install plugin using composer:
---
  
```js
{
    "pawellen/data-tables-listing": "dev-master"
}
```

2. Update Your AppKernel.php
---

```php
$bundles = array (
    (...)
    new PawelLen\DataTablesListing\DataTablesListingBundle()
);
```

3. Configuration:
---

```yaml
data_tables_listing:  
    default_id_property: "id"  
    default_template: LenPanelBundle::listing_div_layout.html.twig  
    include_assets:  
        datatables_js: false  
        datatables_css: false  
        include_jquery: false  
        jquery_js: "//code.jquery.com/jquery-2.1.3.min.js"  
```
  
+ ***default_id_property*** - stands for root entity identifier property, currently used to set ***tr*** id attribute
+ ***default_template***    - allow overwrite default template
+ ***include_assets***      - asset files references (used in render_listing_assets twig function), if set to ***false*** asset wont be included
   - ***datatables_js***       - reference to DataTables js source file
   - ***datatables_css***      - reference to DataTables css source file
   - ***include_jquery***      - decide if jquery should be included, default false
   - ***jquery_js***           - reference to jQuery source file
  
  
4. Add assets to your template using ***render_listing_assets*** twig function:
---

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
  
  
5. Your listing is ready to use :)
---
  

Usage
===

1. Creating new table in controller.
---

To create listing you just need to get ***listing*** service in your controller and pass to it your listing type object.
Example:

```php
    use PawelLen\DataTablesListing\Listing;
    
    (...)
    
    /**
     * @Route("/", name="user_list")
     * @Template()
     */
    public function listAction(Request $request)
    {
        /** @var Listing $listing */
        $listing = $this->get('listing')->createListing(new UserListing(), array(
            'template' => 'LenPanelBundle:User:list.html.twig',
            'request' => $request
        ));

        if ($request->isXmlHttpRequest()) {
            return $listing->createResponse();
        }

        return array(
            'listing' => $listing->createView()
        );
    }
```

As you can see usage of listing is very similar to Symfony forms component. Options:  
 + ***request*** - Request object. Used to get any data passed form your site, for example filters, column order by itp...
 + ***template*** - Template reference. Allow to set custom template
 + ***query_builder*** - QueryBuilder callback - builds query for listing
 + ***process_result_callback*** - callback for processing whole result object
 + ***process_row_callback*** - callback for processing single result row
 + ***order_by*** - dql field, default order when table is loading
 + ***order_direction*** - asc|desc, direction of ***order_by***
 + ***order_column*** - NOT IMPLEMENTED YET
 + ***data_source*** - callback or string, url for ajax requests
 + ***page_length*** - number of records per one page, default is 10
 + ***page_length_menu*** - array that represent page length choices. Value -1 stands for all records, Default is array(10, 25, 50, 100, -1)
 + ***auto_width*** - boolean, switch automatic column width for DataTables plugin
 + ***row_attr*** - Table row attributes, allowed supoptions are:
    - ***id*** - string, property path for tr elements id attributes
    - ***class*** => string, class for tr elements
 + ***order_column*** - array, starting order array for DataTables plugin. Default is array()
 + ***save_state*** - boolean, switch saveState for DataTables plugin
  
  
  
2. Creating own ListingType
---

Creating new listing type looks similar like creation of symfony form.
Example:

```php
    use Doctrine\ORM\QueryBuilder;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use PawelLen\DataTablesListing\Filter\FilterBuilderInterface;
    use PawelLen\DataTablesListing\Column\ColumnBuilderInterface;
    use PawelLen\DataTablesListing\Type\AbstractType;
    
    
    class UserListing extends AbstractType
    {
    
        public function buildFilters(FilterBuilderInterface $builder, array $options)
        {
            $builder->
                add('phrase', 'search', array(
                    'label' => 'Search',
                    'filter' => array(
                        'expression' => '(u.username LIKE ?) OR (u.email LIKE ?) OR (u.firstname LIKE ?) OR (u.lastname LIKE ?)',
                        'eval' => '%like%'
                    )
                ))
            ;
        }
    
    
        public function buildColumns(ColumnBuilderInterface $builder, array $options)
        {
            $builder
                ->add('checkbox', 'checkbox', array(
                    'label' => 'Select [ID]',
                    'property' => 'id',
                    'order_by' => 'u.id'
                ))
                ->add('firstname', 'column', array(
                    'label' => 'First name',
                    'route' => 'user_show',
                    'parameters' => array(
                        'id' => 'id'
                    )
                ))
                ->add('lastname', 'column', array(
                    'label' => 'Last name',
                    'route' => 'user_show',
                    'parameters' => array(
                        'id' => 'id'
                    )
                ))
                ->add('email', 'column', array(
                    'label' => 'Email',
                    'route' => 'user_show',
                    'parameters' => array(
                        'id' => 'id'
                    )
                ))
                ->add('edit', 'button', array(
                    'label' => 'Edit',
                    'route' => 'user_edit',
                    'parameters' => array(
                        'id' => 'id'
                    )
                ))
            ;
        }
    
    
        public function setDefaultOptions(OptionsResolver $resolver)
        {
            $resolver->setDefaults(array(
                'query_builder' => function (QueryBuilder $builder) {
                    $builder->select('u')
                        ->from('LenTreeBundle:User', 'u')
                        ->where('u.isUser = 1');
                },
                'page_length_menu'  => array(1, 2, 3, 10, 25, 50, -1),
                'attr' => array(
                    'novalidate' => 'novalidate'
                ),
                'row_attr' =>array(
                    'id' => 'id',
                    'class' => 'tr-class'
                )
            ));
        }
    
    
        public function getName()
        {
            return 'user_list';
        }
    }
```

This will create listing of users with two filter fields and three columns. ***Data source is pointed in setDefaultOptions*** method.  
Instead of "query_bulder" you can also simply pass "***class***" option to load all entities that type.

```php
    $resolver->setDefaults(array(
        'class' => 'LenTreeBundle:User'
    ));
```


3. Filters template
---

To render created listing, you must pass ListingView object to your template:
Example:

```twig
{% extends "PanelBundle::base_template.html.twig" %}

{% form_theme list.filters _self %}

{% block listing_filters %}
    <div class="row">
        <div class="col-md-6">
            {{ form_row(form.name) }}
        </div>
        <div class="col-md-6">
            {{ form_row(form.email) }}
        </div>
    </div>
{% endblock listing_filters %}

{% block content_body %}
    {{ render_listing(list) }}
{% endblock %}

```

To render entire listing, you can use ***render_listing()*** twig function. This example shows also how to overwrite filters template. Notice that there is a leading underscore in block name.


4. Cells template
---

To render custom cell template simply add block with name pattern:

```twig
{% _[COLLUMN TYPE]_[COLUMN NAME] %}
```
  
Inside your custom block you can use these twig variables:  
+ ***value*** - value of cell
+ ***name*** - name of cell
+ ***row*** - contains whole database record of row
+ ***column*** - column definition and attributes
+ ***parameters*** - parameters passed to column (use ***parameters*** options with allow to access row properties)
+ ***options*** - options passed to column
  
To enable cells templates you must pass ***template*** option to whole listing with reference to template you want to use.
  
  
Egzamles:

+ Modifying header with name ***id***:
```twig
    {% block _header_id %}
        <th {{ block('listing_widget_attributes') }} width="100">{{ label|trans }}</th>
    {% endblock %}
```
  
+ Modifying button with name ***edit***:
```twig
    {% block _button_edit %}
        <td class="cell-options-wrap">
            <ul class="cell-options">
                <li>
                    <a href="{{ path(route, parameters) }}" class="cell-option--edit">
                    <i class="svg--center svg__options ngapp-svg"></i>
                    {{ label }}
                </a>
                </li>
            </ul>
        </td>
    {% endblock %}
```
  
+ Modifying checkbox with name ***idcheck***:
```twig
    {% block _checkbox_idcheck %}
        <td>
            <input type="checkbox" name="{{ name }}[]" value="{{ value }}" id="row_checkbox_{{ row.id }}"/>
            <label for="row_checkbox_{{ row.id }}">[{{ row.id }}]</label>
        </td>
    {% endblock %}
```





Functions
===

1. Creating links
---

To create link you need to add ***link*** option inside ***buildListing*** method.

```php
    $builder->add('name', 'column', array(
        'label' => 'User name',
        'route' => 'user_edit',
        'parameters' => array(
            'user_id' => 'id'
        )
    ));
```

where:  
    ***id*** is a ***propertyPath*** string.  
    ***route*** is a route name  
    ***parameters*** are parameters to generateUrl function  
  

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

In this case country will be joined only if country filter is passed by user, otherwise joins are not used.


5. Buttons
---

Inside ***buildListing*** method you can add action buttons to your list position.
Example:

```php
    $builder->add('edit', 'button', array(
        'label' => 'Edit',
        'route' => 'user_edit',
        'parameters' => array(
            'user_id' => 'id'
        )
    ))
```

6. Checkboxes
---

Inside ***buildListing*** method you can add checkboxes to your list.
Example:

```php
   $builder->add('idcheck', 'checkbox', array(
        'label' => 'Select',
        'property' => 'id',
        'order_by' => 'p.id'
    ))
```

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


8. Events
---
When you need dynamically modify/extend table rows or filters search criteria you can use one of listing events.
(...)


