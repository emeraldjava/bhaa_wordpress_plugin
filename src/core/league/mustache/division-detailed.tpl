<table class="" width="95%">
<thead>
<tr>
<th>Pos</th>
<th>Athlete</th>
{{# events }}
<td><a href="/?post_type=event&p={{eid}}">{{etag}}</a></td>
{{/ events }}
<th>Total</th>
</tr>
</thead>
<tbody>
{{# summary }}
<tr>{{> summary_row_detailed }}</tr>
{{/ summary }}
</tbody>
</table>