$(function(){
	$('.user-list-sort-item a').on('click', function(e){
		var sortType = $(this).attr('data-sort-type');
		window.location.href = G_BASE_URL + '/people/sort_type-' + sortType;
	});
});