<div class="custom-gps-map-wrapper">
    <canvas id="myCanvas"></canvas>

    <div class="map-controls" style="position: fixed; bottom: 30px; left: 30px;">
        <button id="zoomInButton">+</button>
        <button id="zoomOutButton">-</button>
    </div>
</div>

<style>
    .custom-gps-map-wrapper {
        position: relative;
        width: 100%;
        height: 100vh;
        overflow: hidden;
        line-height: 0;
    }

    .custom-gps-map-wrapper #myCanvas {
        display: block;
        width: 100%;
        height: 100%;
        vertical-align: top;
    }

    .custom-gps-map-wrapper .map-controls {
        line-height: normal;
    }
</style>
