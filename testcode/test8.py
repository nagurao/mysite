import requests
import json

url = "curl -f -k -H 'Accept: application/json' -H 'Authorization: Bearer eyJraWQiOiI3ZDEwMDA1ZC03ODk5LTRkMGQtYmNiNC0yNDRmOThlZTE1NmIiLCJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJhdWQiOiIxMjIxMTYwMzY0MzEiLCJpc3MiOiJFbnRyZXoiLCJlbnBoYXNlVXNlciI6Im93bmVyIiwiZXhwIjoxNzA0Mzg3MDc4LCJpYXQiOjE2ODg4MzUwNzgsImp0aSI6ImViNDcyNTJkLWZiMjItNGFjNi05NzQ3LTMxMTc1MWFjZjc4OSIsInVzZXJuYW1lIjoibmFndXJhby5iYXN1ZGVAZ21haWwuY29tIn0.UOGaui-KSV6_CwB31qJkVJ65zch0YNiZFaBpztxi6ovxUZtybJQdmOjNiLH3HqWw8hRiw7jRA0dmvruYrXArdQ' -X GET https://envoy.local/production.json"
response = requests.get(url)  # Make the cURL request

if response.status_code == 200:  # Check if the request was successful
    json_data = json.loads(response.text)  # Parse the JSON response
    # Process the JSON data as needed
    print(json_data)
else:
    print("Error:", response.status_code)
