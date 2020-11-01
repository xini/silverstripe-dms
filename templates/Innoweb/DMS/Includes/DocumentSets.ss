<% if $FilteredDocumentSets %>
<div class="DMS">
    <ul class="DMS__sets">
        <% loop $FilteredDocumentSets %>
           	<% include Innoweb/DMS/DocumentSet %>
        <% end_loop %>
    </ul>
</div>
<% end_if %>
