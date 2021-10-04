#!/usr/bin/env node
let fs = require("fs"),
  {ChartJSNodeCanvas} = require("chartjs-node-canvas");

let argv = process.argv.slice(2),
  width = 355,
  height = 160,
  geojsonUri,
  type = 'svg',
  dest;

for (let i in argv) {
  let split = argv[i].split('=');
  if (split.length === 2) {
    switch (split[0]) {
      case '--geojson':
        geojsonUri = split[1];
        break;
      case '--dest':
        dest = split[1];
        break;
      case '--width':
        width = parseInt(split[1]);
        break;
      case '--height':
        height = parseInt(split[1]);
        break;
      case '--type':
        switch (split[1]) {
          case 'svg':
            type = split[1];
            break
          case 'png':
            type = undefined;
            break;
          default:
            type = 'svg';
            break;
        }
        break;
      default:
        console.warn('Unknown parameter ' + split[0]);
        break;
    }
  }
}

if (!geojsonUri) {
  console.error("Missing geojson uri parameter: --geojson=");
  return;
}

if (!dest) {
  console.error("Missing file destination uri parameter: --dest=");
  return;
}

console.log("Generating elevation chart:");
console.log(" - geojson: " + geojsonUri);
console.log(" - width: " + width);
console.log(" - height: " + height);
console.log(" - file destination: " + dest);

let config = {
  width: width,
  height: height,
  type: type
};

const SLOPE_CHART_SURFACE = {
  ['asphalt']: {
    backgroundColor: '55, 52, 60',
  },
  ['concrete']: {
    backgroundColor: '134, 130, 140',
  },
  ['dirt']: {
    backgroundColor: '125, 84, 62',
  },
  ['grass']: {
    backgroundColor: '143, 176, 85',
  },
  ['gravel']: {
    backgroundColor: '5, 56, 85',
  },
  ['paved']: {
    backgroundColor: '116, 140, 172',
  },
  ['sand']: {
    backgroundColor: '245, 213, 56',
  },
};

try {
  fs.readFile(geojsonUri, "utf8", (err, content) => {
    if (err) {
      console.error(err);
    } else {
      processGeojson(content, config).then(() => {
      });
    }
  });
} catch (e) {
  console.error("Unable to read the file from " + geojsonUri);
  return;
}

async function processGeojson(content, config) {
  try {
    let geojson = JSON.parse(content);

    if (!geojson || !geojson["geometry"] || !geojson["geometry"]["type"]) {
      console.error("Geometry not defined");
      return;
    }
    if (geojson["geometry"]["type"] !== "LineString") {
      console.error("The geometry " + geojson["geometry"]["type"] + " is not supported to generate the elevation chart image");
      return;
    }

    let chartNodeCanvas = new ChartJSNodeCanvas({
        type: config.type, // comment to get the png
        width: config.width,
        height: config.height
      }),
      chartJsOptions = getChartOptions(geojson);

    if (config.type === 'svg') {
      let buffer = chartNodeCanvas.renderToBufferSync(chartJsOptions, 'image/svg+xml');
      fs.writeFileSync(dest, buffer);
    } else {
      let buffer = await chartNodeCanvas.renderToBuffer(chartJsOptions, 'image/png');
      fs.writeFileSync(dest, buffer);
    }

  } catch (e) {
    console.error(e);
  }
}

function getChartOptions(geojson) {
  let surfaceValues = [],
    slopeValues = [],
    labels = [],
    steps = 100,
    trackLength = 0,
    currentDistance = 0,
    previousLocation,
    currentLocation,
    maxAlt = undefined,
    minAlt = undefined,
    usedSurfaces = [],
    chartValues = [],
    surface,
    coordinates = geojson["geometry"]["coordinates"];

  if (coordinates.length < steps) steps = coordinates.length;

  labels.push(0);
  currentLocation = coordinates[0];
  chartValues.push(currentLocation);
  maxAlt = currentLocation[2];
  minAlt = currentLocation[2];

  surface = 'dirt';
  surfaceValues = _setSurfaceValue(
    surface,
    coordinates[0][2],
    [currentLocation],
    surfaceValues
  );
  if (!usedSurfaces.includes(surface)) usedSurfaces.push(surface);
  slopeValues.push([coordinates[0][2], coordinates[0][3] ? coordinates[0][3] : 0]);

  // Calculate track length and max/min altitude
  for (let i = 1; i < coordinates.length; i++) {
    previousLocation = currentLocation;
    currentLocation = coordinates[i];
    trackLength += _getDistanceBetweenPoints(
      previousLocation,
      currentLocation
    );

    if (!maxAlt || maxAlt < currentLocation[2])
      maxAlt = currentLocation[2];
    if (!minAlt || minAlt > currentLocation[2])
      minAlt = currentLocation[2];
  }

  let step = 1,
    locations = [];
  currentLocation = coordinates[0];

  // Create the chart datasets
  for (
    let i = 1;
    i < coordinates.length && step <= steps;
    i++
  ) {
    locations.push(currentLocation);
    previousLocation = currentLocation;
    currentLocation = coordinates[i];
    let localDistance = _getDistanceBetweenPoints(
      previousLocation,
      currentLocation
    );
    currentDistance += localDistance;

    while (currentDistance >= (trackLength / steps) * step) {
      let difference =
          localDistance - (currentDistance - (trackLength / steps) * step),
        deltaLongitude =
          currentLocation[0] - previousLocation[0],
        deltaLatitude =
          currentLocation[1] - previousLocation[1],
        deltaAltitude =
          currentLocation[2] - previousLocation[2],
        longitude =
          previousLocation[0] +
          (deltaLongitude * difference) / localDistance,
        latitude =
          previousLocation[1] +
          (deltaLatitude * difference) / localDistance,
        altitude = Math.round(
          previousLocation[2] +
          (deltaAltitude * difference) / localDistance
        ),
        surface = Object.keys(SLOPE_CHART_SURFACE)[Math.round(step / 10) % (Object.keys(SLOPE_CHART_SURFACE).length - 2)],
        slope = parseFloat(
          (
            ((altitude -
                chartValues[chartValues.length - 1][2]) *
              100) /
            (trackLength / steps)
          ).toPrecision(1)
        );

      let intermediateLocation = [
        longitude,
        latitude,
        altitude
      ];

      chartValues.push(intermediateLocation);

      locations.push(intermediateLocation);
      surfaceValues = _setSurfaceValue(
        surface,
        altitude,
        locations,
        surfaceValues
      );
      locations = [intermediateLocation];
      if (!usedSurfaces.includes(surface)) usedSurfaces.push(surface);
      slopeValues.push([altitude, slope]);

      labels.push(
        parseFloat(((step * trackLength) / (steps * 1000)).toFixed(1))
      );

      step++;
    }
  }

  surfaces = [];
  for (let surface of usedSurfaces) {
    surfaces.push({
      id: surface,
      backgroundColor: SLOPE_CHART_SURFACE[surface].backgroundColor,
    });
  }

  return _getSlopeChartOptions(labels, trackLength, maxAlt, surfaceValues, slopeValues);
}

/**
 * Return the distance in meters between two locations
 *
 * @param point1 the first location
 * @param point2 the second location
 */
function _getDistanceBetweenPoints(point1, point2) {
  let R = 6371e3,
    lat1 = (point1[1] * Math.PI) / 180,
    lat2 = (point2[1] * Math.PI) / 180,
    lon1 = (point1[0] * Math.PI) / 180,
    lon2 = (point2[0] * Math.PI) / 180,
    deltaLat = lat2 - lat1,
    deltaLon = lon2 - lon1;

  let a =
    Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
    Math.cos(lat1) * Math.cos(lat2) * Math.sin(deltaLon / 2) * Math.sin(deltaLon / 2);
  let c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c;
}

/**
 * Set the surface value on a specific surface
 *
 * @param surface the surface type
 * @param value the value
 * @param locations the array of locations with the same surface
 * @param values the current values
 * @returns
 */
function _setSurfaceValue(
  surface,
  value,
  locations,
  values
) {
  let oldSurface = values && values[values.length - 1] && values[values.length - 1].surface ? values[values.length - 1].surface : null;

  if (oldSurface === surface) {
    // Merge the old surface segment with the new one
    values[values.length - 1].values.push(value);
    if (values[values.length - 1].locations.length > 0)
      values[values.length - 1].locations.splice(-1, 1);
    values[values.length - 1].locations.push(...locations);
  } else {
    //Creare a new surface segment
    let nullElements = [];
    if (values && values[values.length - 1] && values[values.length - 1].values) {
      nullElements.length = values[values.length - 1].values.length;
      values[values.length - 1].values.push(value);
    }
    values.push({
      surface,
      values: [...nullElements, value],
      locations,
    });
  }

  return values;
}

/**
 * Return a chart.js dataset for a surface
 *
 * @param values the chart values
 * @param surface the surface type
 * @returns
 */
function _getSlopeChartSurfaceDataset(
  values,
  surface
) {
  return {
    fill: true,
    cubicInterpolationMode: 'monotone',
    tension: 0.3,
    backgroundColor:
    // 'rgb(' + SLOPE_CHART_SURFACE[surface].backgroundColor + ')',
      'rgba(216, 216, 216, 0.7)',
    borderColor: 'rgba(255, 199, 132, 0)',
    pointRadius: 0,
    data: values,
    spanGaps: false,
  };
}

/**
 * Return an RGB color for the given slope percentage value
 *
 * @param value the slope percentage value
 * @returns
 */
function _getSlopeGradientColor(value) {
  let min,
    max,
    proportion = 0,
    step = 15 / 4;

  value = Math.abs(value);

  const SLOPE_CHART_SLOPE_EASY = [67, 227, 9];
  const SLOPE_CHART_SLOPE_MEDIUM_EASY = [195, 255, 0];
  const SLOPE_CHART_SLOPE_MEDIUM = [255, 239, 10];
  const SLOPE_CHART_SLOPE_MEDIUM_HARD = [255, 174, 0];
  const SLOPE_CHART_SLOPE_HARD = [196, 30, 4];

  if (value <= 0) {
    min = SLOPE_CHART_SLOPE_EASY;
    max = SLOPE_CHART_SLOPE_EASY;
  } else if (value < step) {
    min = SLOPE_CHART_SLOPE_EASY;
    max = SLOPE_CHART_SLOPE_MEDIUM_EASY;
    proportion = value / step;
  } else if (value < 2 * step) {
    min = SLOPE_CHART_SLOPE_MEDIUM_EASY;
    max = SLOPE_CHART_SLOPE_MEDIUM;
    proportion = (value - step) / step;
  } else if (value < 3 * step) {
    min = SLOPE_CHART_SLOPE_MEDIUM;
    max = SLOPE_CHART_SLOPE_MEDIUM_HARD;
    proportion = (value - 2 * step) / step;
  } else if (value < 4 * step) {
    min = SLOPE_CHART_SLOPE_MEDIUM_HARD;
    max = SLOPE_CHART_SLOPE_HARD;
    proportion = (value - 3 * step) / step;
  } else {
    min = SLOPE_CHART_SLOPE_HARD;
    max = SLOPE_CHART_SLOPE_HARD;
    proportion = 1;
  }

  let result = ['0', '0', '0'];

  result[0] = Math.abs(
    Math.round(min[0] + (max[0] - min[0]) * proportion)
  ).toString(16);
  result[1] = Math.abs(
    Math.round(min[1] + (max[1] - min[1]) * proportion)
  ).toString(16);
  result[2] = Math.abs(
    Math.round(min[2] + (max[2] - min[2]) * proportion)
  ).toString(16);

  for (let i in result) {
    if (result[i].length === 1)
      result[i] = '0' + result[i];
  }

  return (
    '#' +
    result[0] +
    result[1] +
    result[2]
  );
}

/**
 * Return a chart.js dataset for the slope values
 *
 * @param slopeValues the chart slope values as Array<[chartValue, slopePercentage]>
 * @returns
 */
function _getSlopeChartSlopeDataset(slopeValues) {
  let values = slopeValues.map((value) => value[0]),
    slopes = slopeValues.map((value) => value[1]);

  return [
    {
      fill: false,
      cubicInterpolationMode: 'monotone',
      tension: 0.3,
      backgroundColor: 'rgba(0, 0, 0, 0)',
      borderColor: (context) => {
        const chart = context.chart;
        const {ctx, chartArea} = chart;

        if (!chartArea) {
          // This case happens on initial chart load
          return null;
        }

        let gradient = ctx.createLinearGradient(
          chartArea.left,
          0,
          chartArea.right,
          0
        );

        for (let i in slopes) {
          gradient.addColorStop(
            parseInt(i) / slopes.length,
            _getSlopeGradientColor(slopes[i])
          );
        }
        gradient.addColorStop(
          1,
          _getSlopeGradientColor(slopes[slopes.length - 1])
        );

        return gradient;
      },
      borderWidth: 3,
      pointRadius: 0,
      pointHoverBackgroundColor: '#000000',
      pointHoverBorderColor: '#FFFFFF',
      pointHoverRadius: 6,
      pointHoverBorderWidth: 2,
      data: values,
      spanGaps: false,
    },
    {
      fill: false,
      cubicInterpolationMode: 'monotone',
      tension: 0.3,
      borderColor: 'rgba(255, 255, 255, 1)',
      borderWidth: 8,
      pointRadius: 0,
      data: values,
      spanGaps: false,
    },
  ];
}

/**
 * Create the chart
 *
 * @param labels the chart labels
 * @param length the track length
 * @param maxAltitude the max altitude value
 * @param surfaceValues the surface values
 * @param slopeValues the slope values
 */
function _getSlopeChartOptions(
  labels,
  length,
  maxAltitude,
  surfaceValues,
  slopeValues
) {
  let surfaceDatasets = [];

  for (let i in surfaceValues) {
    surfaceDatasets.push(
      _getSlopeChartSurfaceDataset(
        surfaceValues[i].values,
        surfaceValues[i].surface
      )
    );
  }

  return {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        ..._getSlopeChartSlopeDataset(slopeValues),
        ...surfaceDatasets,
      ],
    },
    options: {
      layout: {
        padding: {
          top: 5,
        },
      },
      maintainAspectRatio: false,
      // Chart.js@2.9.4
      legend: {
        display: false,
      },
      plugins: {
        // Chart.js@3.5.2
        legend: {
          display: false,
        }
      },
      scales: {
        // Chart.js@2.9.4
        yAxes: [{
          // Chart.js@3.5.2
          // y: {
          title: {
            display: false,
          },
          max: maxAltitude,
          ticks: {
            maxTicksLimit: 2,
            maxRotation: 0,
            includeBounds: true,
            // mirror: true,
            z: 10,
            // Chart.js@2.9.4
            padding: 4,
            align: 'end',
            fontColor: '#000000',
            // fontStyle: 500,
            callback: (tickValue, index, ticks) => {
              return tickValue + ' m';
            },
          },
          // Chart.js@2.9.4
          gridLines: {
            // Chart.js@3.5.2
            // grid: {
            drawOnChartArea: true,
            drawTicks: false,
            drawBorder: false,
            borderDash: [10, 10],
            color: '#D2D2D2',
          },
          // Chart.js@2.9.4
        }],
        // Chart.js@3.5.2
        // },
        // Chart.js@2.9.4
        xAxes: [{
          // Chart.js@3.5.2
          // x: {
          title: {
            display: false,
          },
          max: length,
          min: 0,
          ticks: {
            maxTicksLimit: 4,
            maxRotation: 0,
            includeBounds: true,
            fontColor: '#000000',
            // fontStyle: 500,
            callback: (tickValue, index, ticks) => {
              return labels[index] + ' km';
            },
          },
          // Chart.js@2.9.4
          gridLines: {
            // Chart.js@3.5.2
            // grid: {
            color: '#D2D2D2',
            drawOnChartArea: false,
            drawTicks: true,
            drawBorder: true,
            tickLength: 10,
          }
          // Chart.js@2.9.4
        }]
        // Chart.js@3.5.2
        // }
      }
    }
  };
}
