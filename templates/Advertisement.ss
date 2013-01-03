<% if $Zone %>
	<div style="width:$Zone.getWidth;height:$Zone.getHeight;margin:0 auto;padding:0;overflow:hidden;">
	<% if HaveLink %>
		<a <% if UseJSTracking %>class="adlink" data-adid="$ID" <% end_if %>href="$Link" target=\"_blank\">
			$getContent
		</a>
	<% else %>
		$getContent
	<% end_if %>
	</div>
<% end_if %>