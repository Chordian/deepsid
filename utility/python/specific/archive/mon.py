# Create CSV file with specific player entries
import csv

if __name__ == '__main__':

	with open('_specific.csv', 'r', encoding='utf-8') as f:
		content = f.readlines()

	with open('mon.csv', 'w', newline='', encoding='utf-8') as csvfile:
		writer = csv.writer(csvfile, delimiter=',')

		last_sid_line = None

		for line in content:
			if '.sid' in line:
				last_sid_line = line

			if '(MoN' in line and last_sid_line:
				sid_path = last_sid_line.partition('.sid')[0] + '.sid'
				converter = line.partition('(MoN/')[2].partition(')')[0]

				writer.writerow(['_High Voltage SID Collection/' + sid_path, 'MoN/FutureComposer/' + converter])