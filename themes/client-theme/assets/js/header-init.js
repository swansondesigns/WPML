console.log('header-init started');



var sticky = new Waypoint.Sticky({
  element: jQuery('header#masthead')[0]
})



var inview = new Waypoint.Inview({
  element: jQuery('section.home.featured-work')[0],
  enter: function(direction) {
    console.log('Enter triggered with direction ' + direction)
  },
  entered: function(direction) {
    console.log('Entered triggered with direction ' + direction)
  },
  exit: function(direction) {
    console.log('Exit triggered with direction ' + direction)
  },
  exited: function(direction) {
    console.log('Exited triggered with direction ' + direction)
  }
})
