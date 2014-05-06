# Calendar
> Calendar entries for Statamic

## What this does

* Display your date-based entries in a monthly calendar format
* Cycle through months of a year
* Map months to URLs

## What this *doesn't* do

* Recurring events

**They are hard.** With a database they are hard. Without a database? Harder.  
If you have some suggestions on how to handle it, I'm open to ideas.

## Usage

### Set up your monthly calendar routing.
Assuming you want to have URLs like `/calendar/month/2014/05` for May 2014, you'll need to add this to your `_config/routes.yaml`:

~~~
routes:
  /calendar/month/*: calendar_month
~~~

This says: for any URL that starts with `/calendar/month/`, display the `calendar_month` template.

### Set up fields and data
You should keep your events in a folder (you *can* use multiple folders), and they should be saved as date-based entries. eg. `/_content/events/2013-12-31-new-years-eve-party`.

* The datestamp of the entry is the start date.
* An optional field named `end_date` should contain the end date. (eg 2014-05-03)
* An optional field named `start_time` should contain the start time (eg. 3:15pm)
* An optional field named `end_time` should contain the end time (eg. 3:15pm)

### Set up templates
If you're creating a month calendar view, you'll want something along these lines:

~~~
{{ calendar:month folder="calendar" year="{ segment_3 }" month="{ segment_4 }" }}
  <table>
    <thead>
      <tr>
        {{ days_of_week }}
        <th>{{ day_name }}</th>
        {{ /days_of_week }}
      </tr>
    </thead>
    <tbody>
      {{ weeks }}
      <tr>
        {{ days }}
        <td class="{{ if other_month }}other-month{{ endif }} {{ if today }}today{{ endif }}">
          <small>{{ day }}</small>
          {{ entries }}
            <div class="event {{ if all_day }}all-day{{ endif }}">
              {{ unless all_day }}
                <b>{{ start_time }}{{ if end_time }} - {{ end_time }}{{ endif }}:</b>
              {{ endif }}
              {{ title }}
            </div>
          {{ /entries }}
        </td>
        {{ /days }}
      </tr>
      {{ /weeks }}
    </tbody>
  </table>
{{ /calendar:month }}
~~~

This does a number of things:

1. Loops through the `days_of_week` to output Sunday through to Saturday.
2. Loops through `weeks` to create the rows.
3. Loops through the `days` to create the cells. It'll do this based on the year/month parameters.
4. Adds some classes to the cells to indicate whether the day is today or falls within the next/prev months.
5. Inside each day, it loops through the corresponding entries. It'll check the `calendar` folder.
6. In each entry, it adds an `all-day` class as a styling hook.
7. It shows the start and end times if it isn't an all day event.

Your mileage may vary. You will probably need at least steps 1-3.


## Tags

### Month
Provides the ability to output a calendar month.

#### Parameters

Param | Description
--- | ---
`inherit` | Whether or not to inherit from the `set_month` tag. Defaults to `false`.
`month` | 2 digit month.
`year` | 4 digit year.
`folder` | Folder(s) to look for entries. Separate multiple folders by the pipe `|` character.
`cache` | This tag does a lot of work. You will want to cache it. Specify time in seconds. Defaults to 60.

When `inherit` is `true`, the other fields can be omitted. They'll be taken from the `set_month` tag.

#### Example
See 'Set up templates' above for an example.

===

### Month name
Outputs the specified month. It will get the data from the `set_month` tag.

#### Parameters
No parameters. When using the `set_month` tag, this tag will become aware.

#### Example
Assuming your URL is something like `/calendar/month/2014/05` and you have set up the `set_month` tag:
~~~
<h1>{{ calendar:month_name }} {{ segment_3 }}</h1>
Outputs: <h1>May 2014</h1>
~~~

===

### Next Month
Outputs data about the next month.

#### Parameters
No parameters. When using the `set_month` tag, this tag will become aware.

#### Example
Assuming your URL is something like `/calendar/month/2014/05` and you have set up the `set_month` tag:
~~~
{{ calendar:next_month }}
<a href="/calendar/month/{{ year }}/{{ month }}" title="{{ month_name }} {{ year }}">Next</a>
{{ /calendar:next_month }}

Outputs: <a href="/calendar/month/2014/06" title="June 2014">Next</a>
~~~

### Previous Month
Outputs data about the previous month.

#### Parameters
No parameters. When using the `set_month` tag, this tag will become aware.

#### Example
Assuming your URL is something like `/calendar/month/2014/05` and you have set up the `set_month` tag:
~~~
{{ calendar:prev_month }}
<a href="/calendar/month/{{ year }}/{{ month }}" title="{{ month_name }} {{ year }}">Previous</a>
{{ /calendar:prev_month }}

Outputs: <a href="/calendar/month/2014/04" title="April 2014">Previous</a>
~~~

===

### Date Select
Outputs a date selection field.  
When used as a single tag, it will output a `<select>` element. When used as a tag pair, the contents will be parsed for you.

#### Parameters

Param | Description
--- | ---
`year` | 4 digit year. Defaults to whatever is set in the `set_month` tag.
`month` | 2 digit month. Defaults to whatever is set in the `set_month` tag.
`unit` | The interval time unit. Either `month` or `year`. Defaults to `month`.
`from` | The start of the date range. Use plain english or a date. Defaults to `-2 years`. 
`to` | The end of the date range. Use plain english or a date. Defaults to `+2 years`.
`attr` | HTML attributes to be added to the `<select>` when using the single tag mode. Pipe separate key:value pairs. eg. `class:date|name:month`.
`format` | When using single tag mode, this dictates the content of the `<option>` tags. Specify a [PHP date](http://php.net/manual/en/function.date.php) format. Defaults to `F Y` when using `month` units or `Y` when using `year` units.
`placeholder` | When using single tag mode, this dictates the label of the first `<option>`. Defaults to `Select a month` or `Select a year` when using `month` or `year` units respectively.

#### Variables

Var | Description
--- | ---
`date` | The date in `Y-m` format (eg. 2014-05)
`selected` | Returns `true` if the current iteration of matches the specified `year` and `month`.


#### Example

~~~
{{ calendar:date_select attr="class:date-select" year="2014" month="05" from="-2 months" to="+2 months" }}

Outputs:
<select class="date-select">
  <option>Select month</option>
  <option value="2014/03">March 2014</option>
  <option value="2014/04">April 2014</option>
  <option value="2014/05" selected>May 2014</option>
  <option value="2014/06">June 2014</option>
  <option value="2014/07">July 2014</option>
</select>
~~~