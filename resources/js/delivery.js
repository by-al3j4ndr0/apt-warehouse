document.getElementById("origen").onchange = function() {filter_origen()};

function filter_origen() {
  var origen = document.getElementById("origen").value;
  console.log(origen);
}