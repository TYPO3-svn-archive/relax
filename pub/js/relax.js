$(document).ready(function(){

	/*Dynamically add some fields*/
	$('.addmore').click(function(){

		var rfn = generateRandomFieldName();
		$(this).append('<br /> <input type="text" name="tx-relax-pi1['+rfn+'][fieldname]" id="tx-relax-pi1-'+rfn+'" />'+
			'	<input type="text" name="tx-relax-pi1['+rfn+'][fieldvalue]" id="tx-relax-pi1-'+rfn+'[value]" /> ');

	})


	/*Posting Stuff to an Ajax function*/
	$('.document').click(function() {

		$.post('/index.php?eID=relax', {
			docID:$(this).parent().attr('id')
		}, function(data) {

			$('#doctext').css('display','block').html(data);

		});

		return false;
	});

});


function generateRandomFieldName(){
	var chars = "0123456789abcdefghiklmnopqrstuvwxyz";
	var string_length = 8;
	var randomstring = '';
	for (var i=0; i<string_length; i++) {
		var rnum = Math.floor(Math.random() * chars.length);
		randomstring += chars.substring(rnum,rnum+1);
	}

	return randomstring;
}