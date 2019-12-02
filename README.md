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

This plugin requires Craft CMS 3.3.0 or later.

## Using Characteristic

### The Drilldown Helper
```twig
{% set query = craft.entries.section('restaurants') %}
{% set drilldown = craft.characteristic.drilldown('restaurants', query) %}

{% set state = drilldown.state %}

{% set current = drilldown.currentCharacteristic %}

<h2>{{ current.characteristicDescription }}</h2>

{% set options = drilldown.currentOptions.all() %}

<ul>
    {% for option in options %}
        <li><a href="{{ option.applyToDrilldownState(state).url }}">{{ option.value }}</a></li>
        {% if option.featuredImage.exists() %}
        <img src="{{ option.featuredImage.one().url }}" />
        {% endif %}
    {% endfor %}
    <hr>
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

### The characteristic attribute
Characteristic will inject some attributes into your elements to make
querying characteristics easier! Take care to ensure you don't have any
fields with the same handles as these.

- `characteristics` returns a CharacteristicQuery configured to return
only those related to the source element.

```twig
<ul>
    {% for product in craft.entries.section('products').all() %}
    <li>{{product.title}}</li>
    <ul>
        {% for characteristic in product.characteristics.all() %}
        {# We could use .values() without a parameter if we wanted to get all possible values indiscriminately #}
        <li>{{ characteristic.title }} - {{ characteristic.values(restaurant).all()|column('value')|join(', ') }}</li>
        {% endfor %}
    </ul>
    {% endfor %}
</ul>
```
