# Create CSV file with count of files in folders
import os, csv

path = r"C:\Users\jchuu\Music\HVSC\_High Voltage SID Collection"
# path = r"C:\CGSC"

if __name__ == '__main__':

	with open('count.csv', 'w', newline='', encoding='utf-8') as csvfile:
		writer = csv.writer(csvfile, delimiter=',')
		for root, dirs, files in os.walk(path):
			writer.writerow([root, len(files)])