app.service('DataService', ['$http', '$q', '$rootScope', function ($http, $q, $rootScope) {

    /* Private Properties */
    var feedByNetwork = [];
    var feed = [];
    var networks = [];
    var mode = ['getPublicFeed', 'getPrivateFeed', 'search'];
    var currentMode = 0;
    var currentLocation = 0;

    $rootScope.locations = [
        {name: "Worldwide", abbreviation: "W"},
        {name: "USA", abbreviation: "US"},
        {name: "Germany", abbreviation: "DE"}
    ];

    if(!Cookies.get('id')) {
        Cookies.set('id', GUID());
        $rootScope.connectorTooltip = true;
    }

    console.debug("Your ID: " + Cookies.get('id'));

    conn = new WebSocket('ws://' + location.hostname + ':8080/echobot');    

    conn.secureSend = function(x) {
	 try {
            if (conn && conn.readyState === 1) {
                conn.send(x);
            } else {
                serverError();
                console.debug("+++WebSocket is not in OPEN state.+++");
            }
        } catch(ex) {
            serverError();
            console.debug(ex);
        }
    }

    conn.onopen = function () {
        console.debug("+++ WebSocket Connection estbalished+++");
        conn.secureSend('Ping');
        conn.secureSend(JSON.stringify({
            command: 'identify',
            id: Cookies.get('id')
        }));
        checkConnections();
        updateData();
    };

    conn.onerror = function () {
        serverError();
    };

    function serverError() {
        $rootScope.loaderview = false;
        $rootScope.serverError = true;
        $rootScope.$applyAsync();
    }

    conn.onmessage = function (e) {

        var message = JSON.parse(e.data);

        if (message.type === "data") {
            console.debug("--Got: Data");
            console.debug(message);
            feedByNetwork = message.json;
            feed.splice(0, feed.length);
            extractPosts();
            feed = shuffle(feed);
            $rootScope.loaderview = false;
            $rootScope.$apply();
        } else if(message.type === "networks") {
            console.debug("--Got: Networks");
            console.debug(message);
            networks.splice(0, networks.length);
            for(var i = 0, len = message.json.length; i < len; ++i)
                networks.push(message.json[i]);
            $rootScope.$apply();
        } else if (message.type === "message") {
            console.debug(message.message);
        }
    };

    // Public method
    this.getNetworks = function () {
        return networks;
    };

    this.getPostsByNetwork = function () {
        return feedByNetwork;
    };

    this.getPosts = function () {
        return feed;
    };

    /* Private Functions */

    function updateData() {
        console.debug("--Request Update Of Data");
        $rootScope.loaderview = true;
        conn.secureSend(JSON.stringify({
            command: mode[currentMode],
            param: $rootScope.locations[currentLocation].abbreviation,
            query: ("" + angular.element("#searchview-query").val()).replace(/#/g,"")
        }));
    }

    function extractPosts() {
        for (var i in feedByNetwork) {
            if(feedByNetwork.hasOwnProperty(i)) {
                for (var j in feedByNetwork[i].feed) {
                    if(feedByNetwork[i].feed.hasOwnProperty(j)) {
                        feed.push({
                            socialNetwork: {
                                name: feedByNetwork[i].name,
                                icon: feedByNetwork[i].icon,
                                color: feedByNetwork[i].color,
                                customIcon: feedByNetwork[i].customIcon
                            },
                            text: feedByNetwork[i].feed[j].text || "",
                            title: feedByNetwork[i].feed[j].title,
                            author: feedByNetwork[i].feed[j].author,
                            authorPicture: feedByNetwork[i].feed[j].authorPicture,
                            link: feedByNetwork[i].feed[j].link,
                            type: feedByNetwork[i].feed[j].type,
                            tags: feedByNetwork[i].feed[j].tags,
                            createdTime: feedByNetwork[i].feed[j].createdTime,
                            numberOfLikes: feedByNetwork[i].feed[j].numberOfLikes,
                            media: feedByNetwork[i].feed[j].media,
                            thumb: feedByNetwork[i].feed[j].thumb
                        });
                    }
                }
            }
        }
    }

    function shuffle(array) {
        var currentIndex = array.length, temporaryValue, randomIndex;
        while (currentIndex !== 0) {
            randomIndex = Math.floor(Math.random() * currentIndex);
            currentIndex -= 1;
            temporaryValue = array[currentIndex];
            array[currentIndex] = array[randomIndex];
            array[randomIndex] = temporaryValue;
        }
        return array;
    }

    this.getLocation = function () {
        return currentLocation;
    };

    this.getMode = function () {
        return currentMode;
    };

    this.setMode = function (index) {
        currentMode = index;
        updateData();
    };

    this.setLocation = function (index) {
        currentLocation = index;
        updateData();
    };
}]);
