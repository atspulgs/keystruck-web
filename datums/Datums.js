//This will need another rewrite to make functions more stable and catch out unwanted input.
function Datums(date) {
	if(!(date instanceof Date)) return undefined;
	this.date = date;
    this.day = date.getDate();
    this.month = date.getMonth()+1;
    this.year = date.getFullYear();
    Datums.isLeapYear = function(year) {
	    if(year%4 == 0) {
	        if(year%100 == 0) {
	            if(year%400 == 0)
	                return true;
	        } else return true;
	    }
	    return false;
	};
	this.leapYear = Datums.isLeapYear(this.year);
	this.days = this.leapYear? 366:365;
    Datums.getDaysInMonths = function(year) {
    	var daysInMonths = [31,28,31,30,31,30,31,31,30,31,30,31];
		if(Datums.isLeapYear(year)) daysInMonths[1] = 29;
		return daysInMonths;
	}
	this.daysInMonths = Datums.getDaysInMonths(this.year);
    this.getWeekDay = function() {
    	switch(this.date.getDay()){
	        case 0: return this.weekDay = "Sunday";
	        case 1: return this.weekDay = "Monday";
	        case 2: return this.weekDay = "Tuesday";
	        case 3: return this.weekDay = "Wednesday";
	        case 4: return this.weekDay = "Thursday";
	        case 5: return this.weekDay = "Friday";
	        case 6: return this.weekDay = "Saturday";
	        default: return this.weekDay =  null;
	    }
	};
	this.weekDay = this.getWeekDay();
	Datums.getMonthName = function(month) {
	    switch(month) {
	        case 1: return "January";
	        case 2: return "February";
	        case 3: return "March";
	        case 4: return "April";
	        case 5: return "May";
	        case 6: return "June";
	        case 7: return "July";
	        case 8: return "August";
	        case 9: return "September";
	        case 10: return "October";
	        case 11: return "November";
	        case 12: return "December";
	    }
	};
	this.getDayOfYear = function() {
	    var days = 0, dim = Datums.getDaysInMonths(this.year);
	    for(var i = 0; i < this.month-1; i++)
	        days += dim[i];
	    return days+this.day;
	}
	this.dayOfYear = this.getDayOfYear();
	this.getWeek = function() {
	    var firstDay = (new Date(this.year,0,1)).getDay;
	    var week = parseInt((this.dayOfYear+6) /7);
	    if(this.weekDay.num < firstDay && this.weekDay.num != 0) week++;
	    return week;
	};
	this.week = this.getWeek();
	Datums.getDateFromDay = function(day,year) {
		if(day<1 || day>366 ||(!Datums.isLeapYear(year) && day == 366)) {
			console.error("Notice: You provided day as "+day+" and year as "+year
			+". Days in a year can be between 1 and 365 inclusivly and 366 days on leapyears!");
			return 'undefined';
		}
	    var month = 0, dim = Datums.getDaysInMonths(year);
	    if(dim[month] < day) 
	  		while(day > dim[month]) {
	            day -= dim[month];
	            month++;
	    	}
	    return new Datums(new Date(year,month,day));
	};
	/* --------
	** This function can display the current date stored in this object in multiple ways.
	** Default: 1st of January, 2014
	** HTML superscript: 			'html_sup' 			- 1<sup>st</sup> of January, 2014
	** Numeral European: 			'num_eu' 			- 1/1/2014
	** Numeral European short: 		'num_eu_short' 		- 1/1/14
	--------- */
	this.toString = function(type){
		switch(type){
			case "num_eu": return this.day+"/"+this.month+"/"+this.year;
			case "num_eu_short": return this.day+"/"+this.month+"/"+(this.year%100);
			case "html_sup": return this.day+"<sup>"+Datums.getOrdinalSuffix(this.day)+"</sup> of "+Datums.getMonthName(this.month)+", "+this.year;
			default: return this.day+Datums.getOrdinalSuffix(this.day)+" of "+Datums.getMonthName(this.month)+", "+this.year;
		}
	};
	this.getRelativeDay = function(offset) {
		if(offset == 0) return this;
    	var day = this.dayOfYear;
    	var year = this.year;
    	var sign = offset > 0? 1: -1;
    	var ty = parseInt(offset/365);
    	offset = offset%365;
    	for(var i = 0; i < (ty>0?ty:~ty+1); i++) {
    		if(Datums.isLeapYear(year+i))
    			if(offset > 0) --offset;
    			else if(offset < 0) ++offset;
    			else {
    				offset = 365*sign;
    				ty>0?ty--:ty++;
    			}
    	}
    	year +=ty;
    	var nd = day + offset;
    	var lpd = Datums.isLeapYear(year)?366:365
    	if(nd > lpd) {
    		++year;
    		nd -= lpd;
    	} else if(nd == lpd) {
    		++year;
    		nd = 1;
		}else if(nd == 0) {
    		--year;
    		nd = Datums.isLeapYear(year)?366:365;
    	} else if(nd < 1) {
    		nd = (Datums.isLeapYear(--year)?366:365) + nd;
    	}
	    return Datums.getDateFromDay(nd,year);
	}
	this.getNextDay = function() {
		return this.getRelativeDay(1);
	}
	this.getPreviousDay = function() {
		return this.getRelativeDay(-1);
	}
	Datums.getOrdinalSuffix = function(num) {
		if(num%100 == 11) return "th";
		else if(num%100 == 12) return "th";
		else if(num%100 == 13) return "th";
		else switch(num%10) {
			case 1: return "st";
			case 2: return "nd";
			case 3: return "rd";
			default: return "th";
		}
	};
}