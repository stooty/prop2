import anthropic
import sys
import json
import sqlite3
import base64

def get_file_content(file_id, cursor):
    cursor.execute("SELECT file_path, type FROM uploads WHERE id = ?", (file_id,))
    result = cursor.fetchone()
    if result:
        file_path, file_type = result
        with open(file_path, 'rb') as file:
            return base64.b64encode(file.read()).decode('utf-8'), file_type
    return None, None

def generate_proposal(resume_file, job_description_file):
    client = anthropic.Anthropic(
        api_key="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"
    )

    messages = [
        {"role": "human", "content": [
            {"type": "text", "text": "I'm going to provide you with a resume and a job description. First, I need you to extract the text from these files using OCR. Then, create a compelling proposal for the freelancer to send to the employer based on the extracted information."},
        ]},
        {"role": "assistant", "content": "Certainly! I'd be happy to help you extract text from the resume and job description files using OCR, and then create a compelling proposal based on that information. Please provide the files, and I'll get started with the text extraction and proposal generation."},
        {"role": "human", "content": [
            {"type": "text", "text": "Here are the files. The first one is the resume, and the second one is the job description."},
            {"type": "image", "source": {"type": "base64", "media_type": "image/png", "data": resume_file}},
            {"type": "image", "source": {"type": "base64", "media_type": "image/png", "data": job_description_file}},
        ]},
        {"role": "assistant", "content": "I've successfully received and processed both the resume and job description files. I'll now extract the text from these files and create a compelling proposal based on the information."},
        {"role": "human", "content": "Great! Now that you have extracted the text from both files, please create a compelling proposal for the freelancer to send to the employer. The proposal should: 1. Be attention-grabbing and concise 2. Highlight how the freelancer's experience and skills match the job requirements 3. Include a bullet point list of steps the freelancer will take to complete the job"},
    ]

    response = client.messages.create(
        model="claude-3-5-sonnet-20240620",
        max_tokens=1000,
        temperature=0.7,
        messages=messages
    )

    return response.content

def main(resume_id, job_description_id):
    conn = sqlite3.connect('database.sqlite')
    cursor = conn.cursor()
    
    resume_file, _ = get_file_content(resume_id, cursor)
    job_description_file, _ = get_file_content(job_description_id, cursor)
    
    if resume_file and job_description_file:
        proposal = generate_proposal(resume_file, job_description_file)
        conn.close()
        return json.dumps({'success': True, 'proposal': proposal})
    else:
        conn.close()
        return json.dumps({'success': False, 'message': 'Failed to retrieve file contents.'})

if __name__ == "__main__":
    resume_id = int(sys.argv[1])
    job_description_id = int(sys.argv[2])
    print(main(resume_id, job_description_id))