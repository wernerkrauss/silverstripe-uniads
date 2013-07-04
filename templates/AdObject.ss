<% if $Zone %>
	<div style="width:$Zone.getWidth;height:$Zone.getHeight;margin:0 auto;padding:0;overflow:hidden;">
	<% if $HaveLink %>
		<a href="$Link"<% if $use_js_tracking %> data-adid="$ID"<% end_if %><% if $NewWindow %> target=\"_blank\"<% end_if %>>
			$getContent
		</a>
	<% else %>
		$getContent
	<% end_if %>
	</div>
<% end_if %>