var https = require('follow-redirects').https;
var fs = require('fs');

var options = {
  'method': 'GET',
  'hostname': 'envoy.local',
  'path': '/production.json',
  'headers': {
    'Authorization': 'Bearer eyJraWQiOiI3ZDEwMDA1ZC03ODk5LTRkMGQtYmNiNC0yNDRmOThlZTE1NmIiLCJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJhdWQiOiIxMjIxMTYwMzY0MzEiLCJpc3MiOiJFbnRyZXoiLCJlbnBoYXNlVXNlciI6Im93bmVyIiwiZXhwIjoxNjg4Nzg4NzA1LCJpYXQiOjE2ODg3ODUxMDUsImp0aSI6IjhmOTEzODM2LWQxNjctNGIxYi1iNWZlLTMwMmVlNjcyN2U2MCIsInVzZXJuYW1lIjoibmFndXJhby5iYXN1ZGVAZ21haWwuY29tIn0.BWsyS-hZy1iJkfRsGavGsVRubCr5JKJVJ5mKe51u2hMowB1VpJ09cRBlgMONy6ZaGHYigytTC-C63BuLA_cXYA',
    'Cookie': 'sessionId=fxRjzhEEbXDbeo4bMNXXyQfaXccKgUfo'
  },
  'maxRedirects': 20
};

var req = https.request(options, function (res) {
  var chunks = [];

  res.on("data", function (chunk) {
    chunks.push(chunk);
  });

  res.on("end", function (chunk) {
    var body = Buffer.concat(chunks);
    console.log(body.toString());
  });

  res.on("error", function (error) {
    console.error(error);
  });
});

req.end();