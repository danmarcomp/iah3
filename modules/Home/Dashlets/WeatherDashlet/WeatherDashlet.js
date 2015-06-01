Weather = function() {
	return {
		Data: {},
		init: function(id, data) {
			this.Data[id] = data;
			YAHOO.util.Event.onDOMReady(function() {Weather.autoDisplay(id);});
		},
		autoDisplay: function(id) {
			var woeid = Get_Cookie('weather_' + id + '_selected');
			if(! woeid)
				return;
			var row = $('weather_row_'+woeid);
			if(row)
				this.markRow(row, 'mark', woeid, id);
		},
		
		displayWeatherDetails: function(dashlet_id, woeid) {
			var WeatherData = this.Data[dashlet_id];
			if(! WeatherData || ! WeatherData[woeid] || ! WeatherData[woeid].weather_data)
				return;
			var data = WeatherData[woeid];
			Set_Cookie('weather_'+dashlet_id+'_selected', woeid, 30,'/','','');
			
			var map = {
				temp : 'weather_current_condition',
				chill: 'weather_windchill',
				pressure : 'weather_barometer',
				humidity : 'weather_humidity',
				visibility : 'weather_visibility',
				wind : 'weather_wind',
				sunrise : 'weather_sunrise',
				sunset : 'weather_sunset',
				forecast1_day : 'weather_forecast1_day',
				forecast1_text : 'weather_forecast1_text', 	
				forecast1_low : 'weather_forecast1_low', 	
				forecast1_high : 'weather_forecast1_high',
				forecast2_day : 'weather_forecast2_day',
				forecast2_text : 'weather_forecast2_text', 	
				forecast2_low : 'weather_forecast2_low', 	
				forecast2_high : 'weather_forecast2_high'				
			};

			for (var i in map) {
				$(map[i]+'_'+dashlet_id).innerHTML = data.weather_data[i];
			}
			
			var ext_link = $('weather_ext_link_'+dashlet_id);
			if(ext_link) {
				ext_link.href = data.weather_data['yahoo_weather_link'];
			}
			$('city_name_'+dashlet_id).innerHTML = data.name;
			$('weather_details_'+dashlet_id).style.display='';
			setTimeout(function() {
				YDom.removeClass('weather_details_'+dashlet_id, 'hidden');
			}, 10);
			
			return true;
		},

		hideWeatherDetails : function(dashlet_id) {
			Set_Cookie('weather_'+dashlet_id+'_selected', '', 30,'/','','');
			$('weather_details_'+dashlet_id).style.display='none';
			YDom.addClass('weather_details_'+dashlet_id, 'hidden');
		},
		
		searchLocations: function(dashlet_id) {
            var form = SUGAR.ui.getForm('configure_' + dashlet_id );
            var city_name = form.add_city_name.value;
			SUGAR.sugarHome.callMethod(dashlet_id, 'searchLocations', 'location='+encodeURIComponent(city_name), false, showLocations);
		},
		
		addCity: function(dashlet_id) {
            var form = SUGAR.ui.getForm('configure_' + dashlet_id );
            var woeid = getCheckedValue(document.getElementsByName("add_city_woeid"));
            var newCityName = form.add_city_name.value;

            form.add_city_name.value = '';
            $("add_city_but").style.display = "none";
            $("add_city_data").innerHTML = '';

            var cities_input = SUGAR.ui.getFormInput(form, 'cities');
            var row = {depth: 0, name: newCityName, woeid: woeid};
            cities_input.insertRow(row);
		},
		
		clearCities: function() {
			$("add_city_but").style.display = "none";	
			$("add_city_data").innerHTML = "";
		},
		
		undoPrevMark: null,
		markRow: function(therow, action, id, dashlet_id) {
			if (! therow.marked) {
				if(this.undoPrevMark && this.undoPrevMark[0] != therow) {
					this.undoPrevMark[1] = 'clear';
					sListView.row_action.apply(window, this.undoPrevMark);
				}
				if(this.displayWeatherDetails(dashlet_id, id))
					this.undoPrevMark = arguments;
				else
					return;
			} else {
				this.hideWeatherDetails(dashlet_id);
				this.undoPrevMark = null;
			}
			return sListView.row_action(therow, action, id, ! therow.marked);
		},

        submit: function(dashlet_id) {
            var form = SUGAR.ui.getForm('configure_' + dashlet_id);
            SUGAR.ui.getFormInput(form, 'cities').beforeSubmitForm();
            return SUGAR.sugarHome.postForm('configure_' + dashlet_id, SUGAR.sugarHome.uncoverPage);
        }
	};
}();

function showLocations(data) {
	var locations = [];
	var woeid = "";
	var placeTypeName = "";
	var name = "";
	var country = "";
	var dataObj = {total: 0};
	if(data)
		dataObj = JSON.parse(data);
	var radio = null;
	var span = null;
	var hidden = null;
	
	for (var i in dataObj) {
		var places = dataObj[i];
		for (var j in places) {
			locations[j] = places[j]
		}		
	}

	var dataElem = $("add_city_data");

	$("add_city_but").style.display = "none";	
	dataElem.innerHTML = "";
	
	if (locations.total > 0) {
		
		for (i = 0;  i < locations.place.length; i++) {
			woeid = locations.place[i].woeid;
			placeTypeName = locations.place[i].placeTypeName;
			name = locations.place[i].name;
			country = locations.place[i].country;
			
			radio = document.createElement("input");
			radio.type = "radio";
			radio.name = "add_city_woeid";
			radio.value = woeid;
			if (i == 0) radio.checked = true;
			dataElem.appendChild(radio);
			
			span = document.createElement("span");
			span.innerHTML = "&nbsp;" + name +" - "+ country +" ("+ placeTypeName +")<br />";
			dataElem.appendChild(span);
			
			hidden = document.createElement("input");
			hidden.type = "hidden";
			hidden.id = woeid + "_new_city";
			hidden.value = name;
			dataElem.appendChild(hidden);			
		}
		
		$("add_city_but").style.display = "";
	}	
}

function getCheckedValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return "";
}