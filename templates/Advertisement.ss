<div style="width: {$Width}px; height: {$Height}px; margin: 0; padding: 0;">
<% if HaveLink %>
	<a <% if UseJSTracking %>class="adlink" <% end_if %>href="$Link" adid="$ID" target=\"_blank\"> 
		$AdContent
	</a>
<% else %>
	$AdContent
<% end_if %>
</div>