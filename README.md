# Characteristic plugin for Craft CMS

Characteristic provides a new way of storing and querying complex
descriptive relationships and attributes for elements. 

You can think of it as an "attribute-value" system, where an attribute
may be applied to an element (think "Tags") and a specific value may 
applied to that attribute (Think, a Super Table fields with a tag or 
category and a text field)

For example, consider you have a list of local restaurants and you want
to build a system to help visitors pick a restaurant with their
requirements. You *could* create a series of tags or categories, such:
- "Has outdoor seating"
- "Serves pizza"
- "Cash only"

However, this data is completely unaware of context and is really just
a cloud of ideas. Further, what if we wanted to introduce price points,
such as "$", "$$", "$$$", "$$$$", "$$$$$"

Do we now create a new category group or do we just toss it in with the
others? If we create a new group, we have to now write more code.

Characteristic solves this by creating two new element types, sorted
into groups. The "Characteristic" element can be applied like a tag,
once applied, a "Characteristic Value" can be applied. This value is
free-form and new elements will be created as new distinct values
are created ***PER-CHARACTERISTIC***. 

For example, as a developer, I would create a Characteristic Group
called "Restaurant Finder Attributes" and within it, the content editors
can define their characteristics like any other element:
- "Accepts Cash"
- "Price Level"
- "Seating Outdoors"
- "Which dish looks most delicious?"
- "How many vodka-tonics before I get banned?"

Through the use of a custom field, I can now apply these characteristics
as needed with values, as needed. Notice the last option above: it asks 
to pick a photo that I like. This works because both characteristics and
characteristic values can supply their own field layouts per group!

Characteristic provides a helper with a Twig variable called "Drilldown"

The drilldown tool allows you to provide an element query, such as:
`craft.entries.section('restaurants')`

and receive access to the most relevant characteristic and its options.
The helper also manages a "state" based on selected options, allowing
you to quickly create a "Quiz" to find the most suitable element.

## Requirements

This plugin requires Craft CMS 3.4.0 or later.

## Using Characteristic

### The Drilldown Helper
```twig
{# Any arbitrary base element query #}
{% set query = craft.entries.section('restaurants') %}

{# Create an instance of the drilldown helper for the characteristic group with the handle `restaurantCharacteristics` #}
{% set drilldown = craft.characteristic.drilldown('restaurantCharacteristics', query) %}

{% set state = drilldown.state %}

{% set current = drilldown.currentCharacteristic %}

{# Get a text field called characteristicDescription off of the characteristic #}
<h2>{{ current.characteristicDescription }}</h2>

{# Get all of the options available for the current characteristic #}
{% set options = drilldown.currentOptions.all() %}

<ul>
    {% for option in options %}
{# Use the applyToDrilldownState method to create a URL for this value based on the current state #}
        <li><a href="{{ option.applyToDrilldownState(state).url }}">{{ option.value }}</a></li>

{# Grab a featuredImage Asset field off of the option #}
        {% if option.featuredImage.exists() %}
        <img src="{{ option.featuredImage.one().url }}" />
        {% endif %}
    {% endfor %}
    <hr>

{# Optional URL to skip the question with picking an answer #}
    <a href="{{ drilldown.skipUrl() }}">Skip Question</a>
</ul>

<hr>
<div><strong>Current Result Set</strong></div>

{% if drilldown.results.count() == 1 %}
<h1>You did it!</h1>
{% endif %}

{% for item in drilldown.results.all() %}
    <div>{{ item.title }}</div>
{% endfor %}
```

### The characteristics field
The field returns a CharacteristicLinkBlockQuery pre-configured for the
element.

#### Get all characteristics on an entry and show them as a table
```twig
<table class="table-auto">
    <thead>
    <tr>
        <th class="px-4 py-2">Characteristic</th>
        <th class="px-4 py-2">Value</th>
    </tr>
    </thead>
    <tbody>
    
    {% set blocks = entry.restaurantAttributes.all() %}
    {% for block in blocks %}
    <tr>
        <td class="border px-4 py-2">{{ block.characteristic.title }}</td>
        {# We're going to create a string out of the characteristic value's text value #}
        <td class="border px-4 py-2">{{ block.values.all()|column('value')|join(', ') }}</td>
    </tr>
    {% endfor %}
    </tbody>
</table>
```

### Querying for elements
There are a few different ways to query for elements with certain characteristics.

You could use the native Craft relationships, for example:
```twig
{% set characteristic = craft.characteristic.characteristics.handle('price').one() %}
{% set value = characteristic.values.value('$').one() %}
{# Get the first restaurant with a price "$" #} 
{% set restaurants = craft.entries.section('restaurants').relatedTo(['and', {targetElement: characteristic.id}, {targetElement: value.id}]) %}
{{ restaurants.one().title }}
```

### Terminology & Concepts
#### Characteristic Group
Contains a collection of Characteristics, its Values, and Links. Allows
you maintain a separation of Characteristics that are unrelated. For
example: 'Product Characteristics', 'Restaurant Characteristics'

#### Characteristic
An Element that represents the descriptive attribute to assign to
another element. For example: "Material", "Flow Rate", "Open on
Sundays". A Characteristic may have custom fields.

#### Characteristic Value
An Element that represents a potential value for a Characteristic. Each
Characteristic Value is relative to a specific Characteristic. A
Characteristic Value has a `value` attribute that is a text string that
is unique to each Characteristic. For example: "Yes", "No", "1.25".

#### Characteristic Link Block
An Element that contains the linkage between a particular
Characteristic, a number of Characteristic Values, the field it was created from,
as well as the element its attached to.

#### Characteristic Field
A custom field that allows you to create Characteristic Links. It may be
used on any element that supports field layouts (Entries, Products,
Categories, etc.). When used in templating, it returns a Query object
for Characteristic Links that is configured for the soruce element and
field.
