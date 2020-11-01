<li class="DMS__document --{$Extension}">
    <h4 class="DMS__document-title">
    	<a class="DMS__document-link" href="$DMSDownloadLink" title="<%t DMSDocument.DOWNLOAD "Download {title}" title=$Title %>">$Title</a>
    </h4>
    <p class="DMS__document-details">
    	<% include Innoweb/DMS/DocumentDetails %>
    </p>
    <% if $Description %>
        <p class="DMS__document-description">$DescriptionWithLineBreaks</p>
    <% end_if %>
</li>
