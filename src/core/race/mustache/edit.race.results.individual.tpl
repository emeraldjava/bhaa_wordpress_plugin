<div class="container">
    <table class="table table-striped table-bordered" width="100%">
        <tr>
            <th>Place</th>
            <th>Name</th>
            <th>Cat</th>
            <th>Company</th>
            <th>Std</th>
            <th>Number</th>
            <th>Time</th>
        </tr>
        {{# runners}}
        <tr id="{{id}}">
            <td>{{#isAdmin}}
                <a target="_self" class="bhaa-url-link" href="./admin.php?page=bhaa_admin_raceresult&raceresult={{id}}">{{position}}</a>{{/isAdmin}}{{^isAdmin}}{{position}}
                {{/isAdmin}}
            </td>
            <td><a class="bhaa-url-link" r="{{runner}}" href="./admin.php?page=bhaa_admin_runner&id={{runner}}">{{firstname}} {{surname}}</a></td>
            <td>{{category}}{{gender}} p{{posincat}}</td>
            <td><a class="bhaa-url-link" href="/?post_type=house&p={{cid}}">{{cname}}</a></td>
            <td>{{standard}}->[{{actualstandard}}]->{{poststandard}}</td>
            <td>{{racenumber}}</td>
            <td>{{racetime}}</td>
        </tr>
        {{/ runners}}
    </table>
</div>