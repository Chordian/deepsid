# Create CSV file with specific player entries
import csv

if __name__ == '__main__':

	editors = (
		'(VoiceTracker)',
		'(Ten_Tracker)',
		'(DoubleTracker)',
		'(Music_Mixer)'
	)

	with open('_specific.csv', 'r', encoding='utf-8') as f:
		content = f.readlines()

	with open('music_assembler.csv', 'w', newline='', encoding='utf-8') as csvfile:
		writer = csv.writer(csvfile, delimiter=',')

		prev_line = ''
		for line in content:
			if any(t in line for t in editors):
				writer.writerow(['_High Voltage SID Collection/'+prev_line[0:prev_line.find('.sid') + 4], 'Music_Assembler/'+line[line.find('(') + 1:line.find(')')]])
			elif '(Music_Assembler/MC)' in line:
				writer.writerow(['_High Voltage SID Collection/'+prev_line[0:prev_line.find('.sid') + 4], 'Music_Assembler/MC'])
			prev_line = line         
            