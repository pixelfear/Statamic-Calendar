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