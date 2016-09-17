# Calendar

![](https://img.shields.io/badge/statamic-v1-lightgrey.svg?style=flat-square)

* Display your date-based entries in a monthly calendar format
* Cycle through months of a year
* Output entries for a given month or day
* Map months and/or days to URLs

This addon intentionally does _not_ handle recurring events.  
If you have some suggestions on how to handle it, feel free to open a pull request.


## Usage

### Set up fields and data
You should keep your events in a folder, and they should be saved as date-based entries. eg. `/_content/events/2013-12-31-new-years-eve-party.md`.

* The datestamp of the entry is the start date.
* An optional field named `end_date` should contain the end date. (eg 2014-05-03)
* An optional field named `start_time` should contain the start time (eg. 3:15pm)
* An optional field named `end_time` should contain the end time (eg. 3:15pm)

### Set up templates
Depending on the feature/tag you want to use, follow the appropriate template example.


## Set Tag
Allows you to set the date once in your template when using multiple calendar tags.

### Parameters
Param | Description
--- | ---
`year` | 4 digit year. Defaults to the current year.
`month` | 2 digit month. Defaults to the current month.
`day` | 2 digit day. Defaults to the current day.
`folder` | Folder(s) to look for entries. Pipe delimit multiple folders.
`cache` | Specify time in seconds. Defaults to 60.

### Example
```
{{ calendar:set folder="calendar" year="{ segment_3 }" month="{ segment_4 }" }}

Now that the set tag has been used, you don't need to re-specify the date on other tags:
{{ calendar:month inherit="true" }}...{{ /calendar:month }}
```

Note: in previous versions of Calendar, this was `set_month`. It does the same thing.


## Month Tag
Provides the ability to output a calendar month.

### Parameters

Param | Description
--- | ---
`inherit` | Whether or not to inherit from the `set` tag. Defaults to `false`.
`month` | 2 digit month.
`year` | 4 digit year.
`folder` | Folder(s) to look for entries. Separate multiple folders by the pipe `|` character.
`cache` | This tag does a lot of work. You will want to cache it. Specify time in seconds. Defaults to 60.

When `inherit` is `true`, the other parameters can be omitted. They'll be taken from the `set` tag.

### Example

The following will output a traditional calendar month view, and assumes you are on a URL like `/calendar/month/2016/05`.

```
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
```

This does a number of things:

1. Loops through the `days_of_week` to output Sunday through to Saturday.
2. Loops through `weeks` to create the rows.
3. Loops through the `days` to create the cells. It'll do this based on the year/month parameters.
4. Adds some classes to the cells to indicate whether the day is today or falls within the next/prev months.
5. Inside each day, it loops through the corresponding entries. It'll check the `calendar` folder.
6. In each entry, it adds an `all-day` class as a styling hook.
7. It shows the start and end times if it isn't an all day event.

Your mileage may vary. You will probably need at least steps 1-3.


## Month Name Tag
Outputs the specified month. It will get the data from the `set` tag.

### Parameters
No parameters. When using the `set` tag, this tag will become aware.

### Example
Assuming your URL is something like `/calendar/month/2014/05` and you have set up the `set` tag:

```
<h1>{{ calendar:month_name }} {{ segment_3 }}</h1>
Outputs: <h1>May 2014</h1>
```

## Next Month Tag
Outputs data about the next month.

### Parameters
No parameters. When using the `set` tag, this tag will become aware.

### Example
Assuming your URL is something like `/calendar/month/2014/05` and you have set up the `set` tag:

```
{{ calendar:next_month }}
<a href="/calendar/month/{{ year }}/{{ month }}" title="{{ month_name }} {{ year }}">Next</a>
{{ /calendar:next_month }}

Outputs: <a href="/calendar/month/2014/06" title="June 2014">Next</a>
```


## Previous Month Tag
Outputs data about the previous month.

Works the same as the `next_month` tag, but in the opposite direction.


## Date Select Tag
Outputs a date selection field.  
When used as a single tag, it will output a `<select>` element. When used as a tag pair, the contents will be parsed for you.

### Parameters

Param | Description
--- | ---
`year` | 4 digit year. Defaults to whatever is set in the `set` tag.
`month` | 2 digit month. Defaults to whatever is set in the `set` tag.
`unit` | The interval time unit. Either `month` or `year`. Defaults to `month`.
`from` | The start of the date range. Use plain english or a date. Defaults to `-2 years`. 
`to` | The end of the date range. Use plain english or a date. Defaults to `+2 years`.
`attr` | HTML attributes to be added to the `<select>` when using the single tag mode. Pipe separate key:value pairs. eg. `class:date|name:month`.
`format` | When using single tag mode, this dictates the content of the `<option>` tags. Specify a [PHP date](http://php.net/manual/en/function.date.php) format. Defaults to `F Y` when using `month` units or `Y` when using `year` units.
`placeholder` | When using single tag mode, this dictates the label of the first `<option>`. Defaults to `Select a month` or `Select a year` when using `month` or `year` units respectively.

### Variables

Var | Description
--- | ---
`date` | The date of the iteration. Use `format="..."` to display it.
`selected` | Returns `true` if the current iteration of matches the specified `year` and `month`.


### Example

```
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
```

## Month Entries Tag
Outputs entries for a specific month.

### Parameters

Param | Description
--- | ---
`inherit` | Whether or not to inherit from the `set` tag. Defaults to `false`.
`month` | 2 digit month.
`year` | 4 digit year.
`folder` | Folder(s) to look for entries. Separate multiple folders by the pipe `|` character.
`cache` | This tag does a lot of work. You will want to cache it. Specify time in seconds. Defaults to 60.

When `inherit` is `true`, the other parameters can be omitted. They'll be taken from the `set` tag.

### Variables

In addition to your entry’s variables, the following single variables are available inside your calendar:month_entries tag.

Var | Description
--- | ---
`count` | Current count of the entry being displayed.
`first` | `true` if the first entry in the list
`last` | `true` if the last entry in the list
`total_results` | The total number of entries.

### Example

```
{{ calendar:month_entries folder="calendar" year="{ segment_3 }" month="{ segment_4 }" }}
  {{ if no_results }}
    <p>There are no entries this month.</p>
  {{ else }}
    {{ if first }}
      <ul>
    {{ endif }}
        <li>{{ title }}</li>
    {{ if last }}
      </ul>
    {{ endif }}
  {{ endif }}
{{ /calendar:month_entries }}
```

## Day Entries Tag
Outputs entries for a specific day.

### Parameters
Same as the `month_entries` tag, but also accepts a 2 digit `day`.

### Variables
Same as the `month_entries` tag.