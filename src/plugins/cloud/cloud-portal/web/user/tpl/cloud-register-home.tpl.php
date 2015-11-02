<!--
/*
	openQRM Enterprise developed by openQRM Enterprise GmbH.

	All source code and content (c) Copyright 2014, openQRM Enterprise GmbH unless specifically noted otherwise.

	This source code is released under the GNU General Public License version 2, unless otherwise agreed with openQRM Enterprise GmbH.
	The latest version of this license can be found here: src/doc/LICENSE.txt

	By using this software, you acknowledge having read this license and agree to be bound thereby.

				http://openqrm-enterprise.com

	Copyright 2014, openQRM Enterprise GmbH <info@openqrm-enterprise.com>
*/

to debug add {?}
-->

<script type="text/javascript" src="{portaldir}/js/jssor.slider.mini.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	var options = { 
		$AutoPlay: true,
		$SlideshowOptions: { $Class: $JssorSlideshowRunner$, $Transitions: [{ $Duration:2000, $Fade: true, $Opacity:2 }] },
		$DragOrientation : 3,
		$AutoPlayInterval: 10000,
		$SlideDuration: 2000,
		$ArrowNavigatorOptions: {
				$Class: $JssorArrowNavigator$,
				$ChanceToShow: 2,
				$AutoCenter: 2,
				$Steps: 1
		},
		$BulletNavigatorOptions: {
				$Class: $JssorBulletNavigator$,
				$ChanceToShow: 2,
				$ActionMode: 1,
				$AutoCenter: 0,
				$Steps: 1,
				$Lanes: 1,
				$SpacingX: 0,
				$SpacingY: 0,
				$Orientation: 1
		}               
	};
	var jssor_slider1 = new $JssorSlider$('slider1_container', options);    
});
</script>

<div id="register_container">


<div id="slider1_container" style="position: relative; top: 0px; left: 0px; width: 770px; height: 420px;">
	<!-- Slides Container -->
	<div u="slides" style="cursor: move; position: absolute; overflow: hidden; left: 0px; top: 0px; width: 770px; height: 420px;">
		<div><img u="image" src="img/slide1.gif" /></div>
		<div><img u="image" src="img/slide2.gif" /></div>
		<div><img u="image" src="img/slide3.gif" /></div>
	</div>

	<!-- Arrow Navigator Skin Begin -->
		<style>
			/* jssor slider arrow navigator skin 01 css */
			/*
			.jssora01l              (normal)
			.jssora01r              (normal)
			.jssora01l:hover        (normal mouseover)
			.jssora01r:hover        (normal mouseover)
			.jssora01ldn            (mousedown)
			.jssora01rdn            (mousedown)
			*/
			.jssora01l, .jssora01r, .jssora01ldn, .jssora01rdn
			{
				position: absolute;
				cursor: pointer;
				display: block;
				background: url(img/arrows.png) no-repeat;
				overflow:hidden;
			}
			.jssora01l { background-position: -3px -33px; }
			.jssora01r { background-position: -63px -33px; }
			.jssora01l:hover { background-position: -123px -33px; }
			.jssora01r:hover { background-position: -183px -33px; }
			.jssora01ldn { background-position: -243px -33px; }
			.jssora01rdn { background-position: -303px -33px; }
			

			/*
			.jssorb03 div           (normal)
			.jssorb03 div:hover     (normal mouseover)
			.jssorb03 .av           (active)
			.jssorb03 .av:hover     (active mouseover)
			.jssorb03 .dn           (mousedown)
			*/
			.jssorb03 div, .jssorb03 div:hover, .jssorb03 .av
			{
				background: url(img/numberplate.png) no-repeat;
				overflow:hidden;
				cursor: pointer;
			}
			.jssorb03 div { background-position: -5px -4px; }
			.jssorb03 div:hover, .jssorb03 .av:hover { background-position: -35px -4px; }
			.jssorb03 .av { background-position: -65px -4px; }
			.jssorb03 .dn, .jssorb03 .dn:hover { background-position: -95px -4px; }			
			
			
		</style>
		<span u="arrowleft" class="jssora01l" style="width: 55px; height: 55px; top: 123px; left: 2px;">
		</span>
		<span u="arrowright" class="jssora01r" style="width: 55px; height: 55px; top: 123px; right: 2px">
		</span>
		<div u="navigator" class="jssorb03" style="position: absolute; bottom: 6px; right: 10px;">
			<div u="prototype" style="position: absolute; width: 21px; height: 21px; text-align:center; line-height:21px; color:white; font-size:12px;"><NumberTemplate></NumberTemplate></div>
		</div>

	</div>
</div>


