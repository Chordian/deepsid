# Create CSV file with count of files in folders
import os, csv

path = "C:\Users\jchuu\Music\HVSC\_High Voltage SID Collection"
# path = "C:\CGSC"

if __name__ == '__main__':

	with open('count.csv', 'wb') as csvfile:
		writer = csv.writer(csvfile, delimiter=',')
		for root, dirs, files in os.walk(path):
			writer.writerow([root, len(files)])