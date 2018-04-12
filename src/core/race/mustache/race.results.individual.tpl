<div class="container-fluid">
 {{#runners}}
    <div class="row row-striped" id="{{id}}">
        <div class="col-sm-5">P{{position}} - {{firstname}} {{surname}}</div>
        <div class="col-sm-4 company">Company: {{cname}}</div>
        <div class="col-sm-3">Time: {{racetime}}</div>
    </div>
    <div class="row row-striped row-minor">
        <div class="col-sm-5">Cat: {{gender}}{{category}} ({{posincat}})</div>
        <div class="col-sm-4">Std: {{standard}}</div>
        <div class="col-sm-3"><i>Bib</i>: {{racenumber}}</div>
    </div>
{{/runners}}
</div>