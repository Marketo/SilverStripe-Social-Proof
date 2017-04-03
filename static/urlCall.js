(function () {
  window.addEventListener(
    'DOMContentLoaded',
    function () {
      var shareCount = document.getElementById('media-share-count');
      var shareResourceUrl = document.getElementById('media-share-url').dataset.url;

      if (shareResourceUrl && shareCount) {
        var url = window.location.href;
        var resourceCall = shareResourceUrl + "/countsfor?urls=" + url;

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
          if (parseInt(this.readyState) === 4 && parseInt(this.status) === 200) {
            var response = JSON.parse(xhttp.responseText);

            if (response.results) {
              var key = Object.keys(response.results)[0];
              var totalCount = response.results[key].Total;

              if (totalCount > 1000000) {
                totalCount = (totalCount / 1000000).toFixed(1) + 'm';
              } else if (totalCount > 100000) {
                totalCount = Math.round((totalCount / 1000)) + 'k';
              } else if (totalCount > 1000) {
                totalCount = (totalCount / 1000).toFixed(1) + 'k';
              }

              shareCount.classList.add('share-count-fetched');
              shareCount.innerHTML = totalCount;
            }
          }
        };
        xhttp.open("GET", resourceCall, true);
        xhttp.send();
      }
    }, true
  );
})();