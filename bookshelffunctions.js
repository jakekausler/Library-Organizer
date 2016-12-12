var bookcase = '<div class="bookcase"></div>';
var bookshelf = '<div class="bookshelf-container"><div class="bookshelf"></div></div>';

var fromLeft = 0;
var fromTop = 0;
var shelfThickness = 31;
var margin = 6;
var textMargin = 8;
var zoom = 1;

var MAKE_SHELF_START_PROGRESS=0.0;
var MAKE_SHELF_END_PROGRESS=3.0;
var COLOR_BOOK_START_PROGRESS=MAKE_SHELF_END_PROGRESS;
var COLOR_BOOK_END_PROGRESS=99.0;
var SAVE_SHELF_PROGRESS=COLOR_BOOK_END_PROGRESS;

var bookshelfdict = [];

function loadPage(selectedBooks, update, allBooks, shelves) {
	if (update) {
		// showProgressBar();
		setProgressAction("Making shelves");
		makePage(allBooks, shelves);
		$(window).on('load', function() {
			setProgressAction("Coloring Books");
			//updateColors(allBooks);
			setTimeout(function(){
				setProgressAction("Saving Shelf Layout");
				moveProgressBar(SAVE_SHELF_PROGRESS);
				saveShelves();
			}, 0);
			setTimeout(function(){
				setProgressAction("Finishing");
				moveProgressBar(100);
			}, 0);
			setTimeout(function(){
				hideProgressBar();
			}, 0);
			fillSearchSelect();
		});
	} else { 
		loadShelves();
	}
}

function fillSearchSelect() {
	$('.booktext').each(function(i) {
		$o = $('<option>');
		$o.text($(this).text());
		$('#search-select').append($o);
	});
	$("#search-entry").on('awesomplete-selectcomplete',function(){
		alert(this.value);
	});
}

function updateColors(allBooks) {
	var colorThief = new ColorThief();
	$.each(allBooks, function(index, book) {
		setTimeout(function() {
			moveProgressBar((((COLOR_BOOK_END_PROGRESS-COLOR_BOOK_START_PROGRESS)*(index))/(allBooks.length)+COLOR_BOOK_START_PROGRESS).toFixed(1));
			var t = document.getElementById('tooltip-image-'+book.id);
			var color = randomColor('#FFFFFF');
			try {
				color = colorThief.getColor(t);
				color = toHex(color[0], color[1], color[2]);
			} catch(err) {
				console.log('Failed on book with id: ' + book.id + ' and title: ' + book.text + ' ---\n' + err);
			}
			var iColor = inverseColor(color);
			$('#book-'+book.id).css('color', iColor);
			$('#book-'+book.id).css('background-color', color);
		}, 0);
	});
}

function inBooks(books, id) {
	for (var i=0; i<books.length; i++) {
		if ('book-'+books[i].id==id) {
			return true;
		}
	}
	return false;
}

function makePage(books, shelves) {
	var i=0;
	$.each(shelves, function(index, bookcase) {
		setTimeout(function() {
			moveProgressBar((((MAKE_SHELF_END_PROGRESS-MAKE_SHELF_START_PROGRESS)*(i))/(books.length)+MAKE_SHELF_START_PROGRESS).toFixed(1));
			i = makeShelf(books, bookcase, i, index);
		}, 0);
	});
}

function makeid(len)
{
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for( var i=0; i < len; i++ )
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    return text;
}

function randomColor(mix) {
	var color = Math.floor(Math.random()*16777215).toString(16);
	var red = parseInt(color.substring(0,2),16);
	var green = parseInt(color.substring(2,4),16);
	var blue = parseInt(color.substring(4),16);
	if (!mix) {
		mix = Math.floor(Math.random()*16).toString(16);
		mix = '#'+mix+mix+mix+mix+mix+mix;
	}
	mix = mix.substring(1);
	rmix = parseInt(mix.substring(0,2), 16);
	gmix = parseInt(mix.substring(2,4), 16);
	bmix = parseInt(mix.substring(4), 16);
	rfinal = Math.floor(((red+rmix)/2)).toString(16);
	rfinal = ("00"+rfinal).slice(-2);
	gfinal = Math.floor(((green+gmix)/2)).toString(16);
	gfinal = ("00"+gfinal).slice(-2);
	bfinal = Math.floor(((blue+bmix)/2)).toString(16);
	bfinal = ("00"+bfinal).slice(-2);
	while (rfinal.length < 2 || bfinal.length < 2 || gfinal.length < 2 || rfinal.indexOf('N')>-1 || bfinal.indexOf('N')>-1 || gfinal.indexOf('N')>-1) {
		color = Math.floor(Math.random()*16777215).toString(16);
		red = parseInt(color.substring(0,2),16);
		green = parseInt(color.substring(2,4),16);
		blue = parseInt(color.substring(4),16);
		rmix = parseInt(mix.substring(0,2), 16);
		gmix = parseInt(mix.substring(2,4), 16);
		bmix = parseInt(mix.substring(4), 16);
		rfinal = Math.floor(((red+rmix)/2)).toString(16);
		rfinal = ("00"+rfinal).slice(-2);
		gfinal = Math.floor(((green+gmix)/2)).toString(16);
		gfinal = ("00"+gfinal).slice(-2);
		bfinal = Math.floor(((blue+bmix)/2)).toString(16);
		bfinal = ("00"+bfinal).slice(-2);
	}
	color = rfinal + gfinal + bfinal;
	return '#'+color;
}

function inverseColor(color) {
	color = color.substring(1);
	color = parseInt(color, 16);
	color = 0xFFFFFF ^ color;
	color = color.toString(16);
	color = ("000000"+color).slice(-6);
	return '#'+color;
}

function toHex(r, g, b) {
	return '#'+componentToHex(r)+componentToHex(g)+componentToHex(b);
}

function componentToHex(c) {
	var hex = c.toString(16);
	return hex.length==1?"0"+hex:hex;
}

function makeShelf(books, caseStruct, offset, caseNum) {
	caseStruct.width *= zoom;
	caseStruct.shelfHeight *= zoom;
	caseStruct.spacerHeight *= zoom;
	caseStruct.paddingRight *= zoom;
	caseStruct.paddingLeft *= zoom;
	caseStruct.bookMargin *= zoom;
	$bcase = $(bookcase);
	$bcase.width(caseStruct.width-caseStruct.paddingRight);
	$bcase.css('padding-right', caseStruct.paddingRight);
	$bcase.height(caseStruct.shelfHeight*caseStruct.numShelves+caseStruct.spacerHeight*(caseStruct.numShelves-1));
	$bcase.html("");
	var width = caseStruct.width-caseStruct.paddingRight;
	var i = offset;
	var curr_shelf = 0;
	while (curr_shelf < caseStruct.numShelves) {
		var curr_width = 0;
		var curr_book = 0;
		while (books[i] && books[i].width*zoom+curr_width <= caseStruct.width-caseStruct.paddingRight) {
			books[i].width *= zoom;
			books[i].height *= zoom;
			var actualMargin = Math.min(caseStruct.bookMargin*2, books[i].width-caseStruct.bookMargin*2);
			books[i].width -= actualMargin;
			$b = makeBook(books[i]);
			$b.css('bottom', 0);
			$bc = $('<div class="book-container" />');
			$bc.height(caseStruct.shelfHeight+caseStruct.spacerHeight);
			$bc.width(books[i].width+actualMargin);
			$bc.append($b);
			$bcase.append($bc);
			curr_width += books[i].width+actualMargin;
			var bsd = {
				title: books[i].text,
				subtitle: books[i].subtitle,
				booknum: curr_book,
				shelfnum: curr_shelf,
				casenum: caseNum
			};
			bookshelfdict.push(bsd);
			i++;
			curr_book++;
		}
		curr_shelf++;
		if (curr_shelf < caseStruct.numShelves) {
			$shelf_spacer = $('<div class="shelf-spacer" />');
			$shelf_spacer.height(caseStruct.spacerHeight);
			$shelf_spacer.width(caseStruct.width);
			$shelf_spacer.css('top', curr_shelf*caseStruct.shelfHeight+caseStruct.spacerHeight*(curr_shelf));
			$bcase.append($shelf_spacer);
		}
	}
	$('#bookcase-area').append($bcase);
	return i;
}

function makeBook(options) {
	if (!options.color) {
		options.color = randomColor('#FFFFFF');
	}
	if (!options.textColor) {
		options.textColor = inverseColor(options.color);
	}
	options = $.extend(true,
	{
		id: -1,
		text: makeid(Math.floor(Math.random()*40+5)),
		height: Math.floor(Math.random()*100+200),
		width: Math.floor(Math.random()*30+20),
		image: 'http://i.istockimg.com/file_thumbview_approve/64101897/5/stock-photo-64101897-blank-book-cover-empty-template-single-brochure-textbook.jpg'
	}, options);
	var fontsize = Math.min(1.25, options.width/20.0);
	//$('#hidden-ruler').css('font-size', fontsize+'em');
	//var $text = $('<span class="booktext">' + options.text.trimToPx(options.height-textMargin*2) + '</span>');
	var $text = $('<span class="booktext" style="width:'+(options.height-10)+'px;">' + options.text + '</span>');
	var $book = $('<div class="book not-selected" style="color:'+options.textColor+';" />');
	$book.attr('id', 'book-'+options.id);
	$book.height(options.height);
	$book.width(options.width);
	$book.css('font-size', fontsize+'em');
	$book.append($text);
	$book.css('background-color', options.color);
	var $tooltip = $('<div class="tooltip-container"><div class="tooltip"><img id="tooltip-image-'+options.id+'" style="width: auto; height: '+options.height+'px;" src="'+options.image+'" alt="'+options.text+'"</img></div></div>');
	$book.append($tooltip);
	$book.attr('onclick', 'clickBook("'+options.id+'")');
	return $book;
}

function clickBook(id) {
	var $entry = $('<input type="hidden" name="bookid" value="'+id+'" />');
	$('#editor-form').append($entry);
	$('#editor-form').submit();
}

function addBooks(books) {
	$.each(books, function(i, book) {
		$('#bookcase').append(makeBook(book));
	});
}

String.prototype.visualLength = function() {
	var ruler = document.getElementById("hidden-ruler");
	ruler.innerHTML = this;
	return ruler.offsetWidth;
};
String.prototype.trimToPx = function(length) {
	var tmp = this;
	var trimmed = this;
	if (tmp.visualLength() > length) {
		trimmed += "...";
		while (trimmed.visualLength() > length) {
			tmp = tmp.substring(0, tmp.length-1);
			trimmed = tmp + "...";
		}
	}
	return trimmed;
};

function loadShelves() {
	$.ajax({
		type: 'POST',
		url: 'ajaxrequests.php',
		data: {
			action: 'readShelves'
		},
		success: function(d) {
			$('#bookcase-area').replaceWith(d);
			$('.book').each(function(index, book) {
				if (inBooks(books, $(book).attr('id'))) {
					$(book).removeClass('not-selected');
				}
			});
			console.log('Loaded Shelves');
			fillSearchSelect();
		},
		dataType: 'text'
	});
}
function saveShelves() {
	var data = $('#bookcase-area').prop('outerHTML');
	$.ajax({
		type: 'POST',
		url: 'ajaxrequests.php',
		data: {
			contents: data,
			action: 'saveShelves'
		},
		success: function(d) {
			console.log('Saved Shelves');
			$('.book').each(function(index, book) {
				if (inBooks(selectedBooks, $(book).attr('id'))) {
					$(book).removeClass('not-selected');
				}
			});
		},
		dataType: 'text'
	});
	data = JSON.stringify(bookshelfdict);
	$.ajax({
		type: 'POST',
		url: 'ajaxrequests.php',
		data: {
			contents: data,
			action: 'saveShelfDict'
		},
		success: function(d) {
			console.log('Saved Shelf Dictionary');
		},
		dataType: 'text'
	});
}

function showProgressBar() {
	$('#progressPopup').css('visibility', 'visible');
}
function moveProgressBar(percent) {
	$('#progressBar').css('width', percent+"%");
	$('#progressLabel').html(percent+"%");
}
function setProgressAction(action) {
	$('#progressAction').html(action);
}
function hideProgressBar() {
	$('#progressPopup').css('visibility', 'hidden');
}