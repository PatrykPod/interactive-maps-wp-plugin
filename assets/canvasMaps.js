document.addEventListener("DOMContentLoaded", () => {

    const canvas = document.getElementById('myCanvas');
    if (!canvas || typeof CUSTOM_GPS_MAP === 'undefined') return;

    const ctx = canvas.getContext('2d');
    const image = new Image();
    
    if (!CUSTOM_GPS_MAP.image) {
        console.warn('No map image configured');
        return;
    }

    image.src = CUSTOM_GPS_MAP.image;
    const points = CUSTOM_GPS_MAP.points || [];
    const MAX_ZOOM_IN = 3; // Adjust this as needed for your application
    let MAX_ZOOM_OUT;
    let zoom = 1;
    let imageWidth = 0;
    let imageHeight = 0;
    let dragStartX = 0;
    let dragStartY = 0;
    let dragging = false;
    let lastLeft = 0;
    let lastTop = 0;
    let newLeft = null;
    let newTop = null;
    const audioIcon = CUSTOM_GPS_MAP.audioIcon;


    /////////////////////////// INTERACTIVE POINTS  ////////////////////////////////

    // Call the fetchAndProcessData function before the DOM starts loading
    // fetchAndProcessData().then(() => {        
        canvas.addEventListener('click', function (event) {
            const rect = canvas.getBoundingClientRect();
            const clickX = event.clientX - rect.left;
            const clickY = event.clientY - rect.top;

            points.forEach(point => {
                const pointX = transX(point.x);
                const pointY = transY(point.y);
                const radius = 25 * zoom * 1.25;

                // Check if the click is within the circle
                if (Math.pow(clickX - pointX, 2) + Math.pow(clickY - pointY, 2) <= Math.pow(radius, 2)) {
                    // Play the audio for the clicked point
                    if (point.audioPath) {
                        // audio.pause();
                        // audio.currentTime = 0;
                        const audio = new Audio(point.audioPath);
                        audio.play();
                    }
                    else if (point.url) {
                        console.log(point.url);
                        // window.open(point.url, '_self');
                    } else {
                        // WE CAN SET POPUP HERE
                    }
                }
            });
        });
    // });


    function drawCirclePoint(context, color, pointObj, r) {
        context.fillStyle = color; // Color of the pin
        context.beginPath();
        context.arc(transX(pointObj.x), transY(pointObj.y), r * zoom * 1.25, 0, 2 * Math.PI); // Draw a circle
        context.fill();
    }


    function drawPoints() {
        points.forEach(point => {
            drawCirclePoint(
                ctx,
                'rgba(255,0,0,.85)',
                point,
                26
            );
        });
    }


    // // ADD ICON IMAGE TO THE POINT:
    // const img = new Image();
    // img.src = pointObj.icon.src;
    // img.onload = () => {
    //   let imgWidth = 63*zoom*1.5;
    //   let imgHeight = 62*zoom*1.5;
    //   // REDRAW IS NECESSAR YTO DRAW A ICON ON THE POINT - STARTING AT CENTER OF CIRCLE POINT
    //   ctx.drawImage(img,pointObj.x * zoom + newLeft, pointObj.y * zoom + newTop, imgWidth, imgHeight);
    // };
    /////////////////////////////////////////////////////////////////////////////////////////


    const debounce = (func, wait, immediate) => {
        let timeout;
        return (...args) => {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    };


    /* CANVAS RENDERING */
    const drawImage = debounce(() => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const left = (canvas.width - imageWidth * zoom) / 2;
        const top = (canvas.height - imageHeight * zoom) / 2;

        var visibleWidth = canvas.width / zoom;
        var visibleHeight = canvas.height / zoom;

        ctx.drawImage(image, left, top, imageWidth * zoom, imageHeight * zoom);
        drawPoints();
    }, 20);


    function redrawCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(image, newLeft, newTop, image.width * zoom, image.height * zoom);
        drawPoints();
    }


    const handleMouseDown = (event) => {
        dragging = true;
        dragStartX = event.clientX;
        dragStartY = event.clientY;
        lastLeft = newLeft;
        lastTop = newTop;
    };


    const handleMouseMove = (event) => {
        if (!dragging) return;

        const deltaX = event.clientX - dragStartX;
        const deltaY = event.clientY - dragStartY;
        const left = (canvas.width - imageWidth * zoom) / 2;
        const top = (canvas.height - imageHeight * zoom) / 2;

        newLeft = Math.max(Math.min(lastLeft + deltaX, 0), canvas.width - imageWidth * zoom);
        newTop = Math.max(Math.min(lastTop + deltaY, 0), canvas.height - imageHeight * zoom);

        redrawCanvas();
    };


    const handleMouseUp = (event) => {
        dragging = false;
        redrawCanvas();
    };


    const handleWheel = (event) => {
        event.preventDefault(); // Prevent the default scroll behavior

        var delta = event.deltaY ? -event.deltaY : event.wheelDelta ? event.wheelDelta : -event.detail;

        var zoomFactor = delta > 0 ? 1.1 : 0.9;

        var mouseX = event.clientX;
        var mouseY = event.clientY;

        zoomAt(mouseX, mouseY, zoomFactor);
    };


    function getPoint(x, y) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: (x - rect.left) / zoom + rect.left / zoom - rect.width / (2 * zoom),
            y: (y - rect.top) / zoom + rect.top / zoom - rect.height / (2 * zoom)
        };
    }


    function zoomAt(mouseX, mouseY, zoomFactor) {
        const canvasRect = canvas.getBoundingClientRect();

        const canvasX = (mouseX - canvasRect.left) * (canvas.width / canvasRect.width);
        const canvasY = (mouseY - canvasRect.top) * (canvas.height / canvasRect.height);

        const previousZoom = zoom;

        zoom = Math.max(MAX_ZOOM_OUT, Math.min(zoom * zoomFactor, MAX_ZOOM_IN));

        if (zoom !== previousZoom) {
            const zoomDiff = zoom / previousZoom;

            newLeft = canvasX - ((canvasX - newLeft) * zoomDiff);
            newTop = canvasY - ((canvasY - newTop) * zoomDiff);

            newLeft = Math.min(0, Math.max(newLeft, canvas.width - image.width * zoom));
            newTop = Math.min(0, Math.max(newTop, canvas.height - image.height * zoom));

            newLeft = Math.round((newLeft + Number.EPSILON) * 100) / 100;
            newTop = Math.round((newTop + Number.EPSILON) * 100) / 100;
        }

        redrawCanvas();
    }


    const handleResize = debounce(() => {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        MAX_ZOOM_OUT = Math.max(canvas.width / imageWidth, canvas.height / imageHeight);
        zoom = Math.max(zoom, MAX_ZOOM_OUT);

        redrawCanvas();
    }, 30);


    const handleLoad = () => {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        imageWidth = image.width;
        imageHeight = image.height;
        MAX_ZOOM_OUT = Math.max(canvas.width / imageWidth, canvas.height / imageHeight);

        newLeft = (canvas.width - imageWidth * MAX_ZOOM_OUT) / 2;
        newTop = (canvas.height - imageHeight * MAX_ZOOM_OUT) / 2;
        zoom = MAX_ZOOM_OUT;

        drawImage();
        drawPoints();
    };


    // UTILS
    canvas.addEventListener('mousedown', handleMouseDown);
    canvas.addEventListener('mousemove', handleMouseMove);
    canvas.addEventListener('mouseup', handleMouseUp);
    canvas.addEventListener('wheel', handleWheel);
    window.addEventListener('resize', handleResize);
    image.addEventListener('load', handleLoad);

    function transX(x) {
        return x * zoom + newLeft
    }
    function transY(y) {
        return y * zoom + newTop
    }

    //////////////////////// NO CANVAS ELEMENTS /////////////////////////////////////////////////////////////////
    // HANDLE ZOOMING BUTTONS:
    document.getElementById('zoomInButton').addEventListener('click', function () {
        zoomAt(window.innerWidth / 2, window.innerHeight / 2, 1.1);
    });

    document.getElementById('zoomOutButton').addEventListener('click', function () {
        zoomAt(window.innerWidth / 2, window.innerHeight / 2, 0.9);
    });



    ///////////////////////
    //// ADMIN PANEL
    ///////////////////////
    
    // Check if the checkbox with the class 'admin-panel' is checked
    const adminPanelCheckbox = document.querySelector('.admin-panel');

    // Add a click event listener to the canvas
    canvas.addEventListener('click', function (event) {
        if (adminPanelCheckbox && adminPanelCheckbox.checked) {
        const rect = canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
    
        // Create a new point object with the clicked coordinates and 'false' values
        const newPoint = {
            x: x,
            y: y,
            url: false,
            icon: false,
            audioPath: false
        };
    
        console.log(`new Point: (${newPoint.x}, ${newPoint.y})`);
        }
    });

});