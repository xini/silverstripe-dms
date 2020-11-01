<% if $SortedDocuments %>
    <li class="DMS__set">
        <% if $Title %>
            <h3 class="DMS__set-title">$Title</h3>
        <% end_if %>

		<ul class="DMS__documents">
	        <% loop $SortedDocuments %>
	            <% include Innoweb/DMS/Document %>
	        <% end_loop %>
	    </ul>
    </li>
<% end_if %>