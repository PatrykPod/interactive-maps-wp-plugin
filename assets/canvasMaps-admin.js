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
    const defaultPinColor = CUSTOM_GPS_MAP.pinColor || '#ff0000';
    const MAX_ZOOM_IN = 3;
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
    const mapPointsSection = document.getElementById('cgm-map-points');
    const pointGrid = mapPointsSection ? mapPointsSection.querySelector('.cgm-point-grid') : null;
    const emptyPointsState = mapPointsSection ? mapPointsSection.querySelector('.cgm-empty-points-state') : null;

    canvas.addEventListener('click', function (event) {
        const clickedPoint = getClickedPoint(event);

        if (clickedPoint) {
            scrollToPointCard(clickedPoint.id);
        }
    });

    function drawCirclePoint(context, color, pointObj, r) {
        context.fillStyle = color;
        context.beginPath();
        context.arc(transX(pointObj.x), transY(pointObj.y), r * zoom * 1.25, 0, 2 * Math.PI);
        context.fill();
    }

    function drawIconPoint(pointObj) {
        const pointPinColor = pointObj.pinColor || defaultPinColor;

        if (!pointObj.pinIconUrl) {
            drawCirclePoint(ctx, pointPinColor, pointObj, 26);
            return;
        }

        if (!pointObj._pinIconImage) {
            const iconImage = new Image();
            iconImage.onload = () => redrawCanvas();
            iconImage.src = pointObj.pinIconUrl;
            pointObj._pinIconImage = iconImage;
            drawCirclePoint(ctx, pointPinColor, pointObj, 26);
            return;
        }

        if (!pointObj._pinIconImage.complete) {
            drawCirclePoint(ctx, pointPinColor, pointObj, 26);
            return;
        }

        const scale = (pointObj.pinIconScale || 50) / 100;
        const width = Math.max(pointObj._pinIconImage.naturalWidth * scale * zoom, 12 * zoom);
        const height = Math.max(pointObj._pinIconImage.naturalHeight * scale * zoom, 12 * zoom);
        ctx.drawImage(
            pointObj._pinIconImage,
            transX(pointObj.x) - width / 2,
            transY(pointObj.y) - height / 2,
            width,
            height
        );
    }

    function drawPoints() {
        points.forEach(point => {
            drawIconPoint(point);
        });
    }

    function getClickedPoint(event) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const clickX = (event.clientX - rect.left) * scaleX;
        const clickY = (event.clientY - rect.top) * scaleY;

        for (const point of points) {
            const pointX = transX(point.x);
            const pointY = transY(point.y);
            const radius = 25 * zoom * 1.25;

            if (Math.pow(clickX - pointX, 2) + Math.pow(clickY - pointY, 2) <= Math.pow(radius, 2)) {
                return point;
            }
        }

        return null;
    }

    function scrollToPointCard(pointId) {
        if (!pointId) {
            return;
        }

        const pointCard = document.getElementById(`cgm-point-card-${pointId}`);

        if (!pointCard) {
            return;
        }

        document.querySelectorAll('.cgm-point-card.is-focused').forEach(card => {
            card.classList.remove('is-focused');
        });

        pointCard.classList.add('is-focused');
        const scrollTop = window.scrollY + pointCard.getBoundingClientRect().top - 24;

        window.scrollTo({
            top: Math.max(scrollTop, 0),
            behavior: 'smooth',
        });
    }

    function appendPointCard(pointData, cardHtml) {
        if (!pointGrid || !cardHtml) {
            return;
        }

        if (emptyPointsState) {
            emptyPointsState.classList.add('is-hidden');
        }

        pointGrid.insertAdjacentHTML('beforeend', cardHtml);

        if (window.jQuery) {
            const $form = window.jQuery(`#cgm-point-card-${pointData.id} .cgm-point-form`);
            const contentType = $form.find('.cgm-content-type-input').val() || 'url';
            $form.attr('data-active-type', contentType);
            window.jQuery(document).trigger('cgm:point-form-added', [$form[0]]);
        }
    }

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

    const drawImage = debounce(() => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const left = (canvas.width - imageWidth * zoom) / 2;
        const top = (canvas.height - imageHeight * zoom) / 2;

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

        newLeft = Math.max(Math.min(lastLeft + deltaX, 0), canvas.width - imageWidth * zoom);
        newTop = Math.max(Math.min(lastTop + deltaY, 0), canvas.height - imageHeight * zoom);

        redrawCanvas();
    };

    const handleMouseUp = () => {
        dragging = false;
        redrawCanvas();
    };

    const handleWheel = (event) => {
        event.preventDefault();

        const delta = event.deltaY ? -event.deltaY : event.wheelDelta ? event.wheelDelta : -event.detail;
        const zoomFactor = delta > 0 ? 1.1 : 0.9;

        zoomAt(event.clientX, event.clientY, zoomFactor);
    };

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

    canvas.addEventListener('mousedown', handleMouseDown);
    canvas.addEventListener('mousemove', handleMouseMove);
    canvas.addEventListener('mouseup', handleMouseUp);
    canvas.addEventListener('wheel', handleWheel);
    window.addEventListener('resize', handleResize);
    image.addEventListener('load', handleLoad);

    function transX(x) {
        return x * zoom + newLeft;
    }

    function transY(y) {
        return y * zoom + newTop;
    }

    document.getElementById('zoomInButton').addEventListener('click', function () {
        zoomAt(window.innerWidth / 2, window.innerHeight / 2, 1.1);
    });

    document.getElementById('zoomOutButton').addEventListener('click', function () {
        zoomAt(window.innerWidth / 2, window.innerHeight / 2, 0.9);
    });

    canvas.addEventListener("dblclick", function (event) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const clickX = (event.clientX - rect.left) * scaleX;
        const clickY = (event.clientY - rect.top) * scaleY;
        const mapX = (clickX - newLeft) / zoom;
        const mapY = (clickY - newTop) / zoom;

        fetch(CUSTOM_GPS_MAP.ajax, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                action: "cgm_add_point",
                x: mapX,
                y: mapY
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                points.push({
                    id: data.data.point.id,
                    pointName: data.data.point.pointName,
                    x: mapX,
                    y: mapY,
                    pinIconUrl: data.data.point.pinIconUrl || "",
                    pinIconScale: data.data.point.pinIconScale || 50,
                    pinColor: data.data.point.pinColor || defaultPinColor,
                    url: data.data.point.url || "",
                    imageUrl: data.data.point.imageUrl || "",
                    audioPath: data.data.point.audioPath || ""
                });

                appendPointCard(data.data.point, data.data.cardHtml || "");
                redrawCanvas();

                window.setTimeout(function () {
                    scrollToPointCard(data.data.id);
                }, 60);
            }
        });
    });
});
