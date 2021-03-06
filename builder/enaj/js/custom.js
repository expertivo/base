// SCRIPTS CUSTOMIZADOS DO PROJETO

//JQUERY
jQuery(function() {

	// GET URL BASE FROM INPUT FIELD INTO TEMPLATE BASE
	var URLBase = jQuery('#baseurl').val();

// EVENTOS RESPONSIVOS

	window.customResponsive = function () {

		// --

	};
	// CHAMADA DA FUNÇÃO
	customResponsive();
	jQuery(window).resize(function() { customResponsive(); }); // ON RESIZE

	// SHOW/HIDE SCROLL-TO-TOP BUTTON

		window.scrollToTop = function() {
			var obj = jQuery('#scroll-to-top');
			var pos = jQuery(window).scrollTop();
			if(pos > 200) obj.fadeIn();
			else obj.fadeOut();
		};
		scrollToTop();
		jQuery(window).scroll(function(){ scrollToTop() });

	// NAV MENU -> Menu Principal

		// Cria barra de rolagem caso o submenu ultrapasse o limite inferior da janela.
		// É necessário por causa da posição fixa do menu,
		// que esconde os itens abaixo do limite inferior da janela
		window.navChildHeight = function() {
			var wH = jQuery(window).height(); // window height
			var hH = jQuery('#header').outerHeight(); // #header height
			var fH = jQuery('#footer').outerHeight(); // #footer height
			var e = jQuery('.nav').find('.nav-child');
			e.each(function() {
				var obj = jQuery(this);
				var H = wH - (hH + fH);
				var rH = getRealHeight(obj);
				obj.css('height', (rH > H ? H : 'auto'));
			});
		};
		window.getRealHeight = function(e) {
			e.css({position:'absolute', visibility: 'hidden', display: 'block'}); // torna o objeto visível para o código
			var h = e[0].scrollHeight; // pega altura real do objeto
			e.css({position: '', visibility: '', display: ''}); // retorna ao padrão do objeto
			return h;
		};
		// run function
		navChildHeight();
		jQuery(window).on('resize', function() { navChildHeight(); }); // on resize

	// MMENU -> Mobile Menu

		if(jQuery("#navigation").length) {
			var $mmenu = jQuery("#navigation").clone();
			$mmenu.removeClass().attr( "id", "mm-navigation" );
			$mmenu.find('.nav.menu').removeClass();
			$mmenu.mmenu({
				navbars: false,
				extensions: ["theme-black", "border-full", "pageshadow"]
			});
			jQuery(window).resize(function() { $mmenu.data("mmenu").close(); });
		}

});
